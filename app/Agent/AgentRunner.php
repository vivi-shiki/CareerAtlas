<?php

namespace App\Agent;

use App\Domain\DomainError;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

final class AgentRunner
{
    public function __construct(private AgentService $service, private PublicSources $sources, private ModelTransport $transport) {}

    public static function declarations(): array
    {
        $string = ['type' => 'STRING'];

        return [
            ['name' => 'search_experiences', 'description' => 'Search selected published fields. Short keyword; empty query discovers available sources. No private records.',
                'parameters' => ['type' => 'OBJECT', 'properties' => ['query' => $string], 'required' => ['query']]],
            ['name' => 'get_experience', 'description' => 'Read selected fields of a published experience from search results.',
                'parameters' => ['type' => 'OBJECT', 'properties' => ['experience_id' => $string], 'required' => ['experience_id']]],
            ['name' => 'finish_answer', 'description' => 'Finish with a grounded answer or an explicit information gap. Citations use retrieved handles and exact excerpts.',
                'parameters' => ['type' => 'OBJECT', 'properties' => ['answer' => $string, 'insufficient_information' => ['type' => 'BOOLEAN', 'description' => 'True for refusals or when any specifically requested fact is missing, even if partial context is cited. False only when published evidence answers the request.'],
                    'citations' => ['type' => 'ARRAY', 'items' => ['type' => 'OBJECT', 'properties' => ['handle' => $string, 'excerpt' => $string], 'required' => ['handle', 'excerpt']]],
                    'follow_up_questions' => ['type' => 'ARRAY', 'items' => $string]], 'required' => ['answer', 'citations', 'insufficient_information']]],
        ];
    }

    public function execute(string $id): void
    {
        if (! DB::table('agent_runs')->where('id', $id)->where('state', 'queued')->update(['state' => 'running', 'progress' => 'Finding published sources', 'started_at' => now()->toIso8601String()])) {
            return;
        }
        $usage = [];
        $tools = [];
        $seen = [];
        try {
            $run = $this->check($id);
            $conversation = $this->service->conversation($run->conversation_id);
            $history = DB::table('agent_runs')->where('conversation_id', $run->conversation_id)->where('state', 'completed')->orderByDesc('created_at')->limit(10)->get()->reverse();
            $context = $history->map(fn ($r) => ['question' => $r->question, 'answer' => json_decode($r->answer, true)['answer']])->values()->all();
            $contents = [['role' => 'user', 'parts' => [['text' => json_encode(['previous_turns' => $context, 'question' => $run->question], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]]]];
            for ($step = 0; $step < config('agent.max_steps'); $step++) {
                $run = $this->check($id);
                $remaining = max(0.1, now()->diffInMilliseconds($run->deadline_at, false) / 1000);
                $declarations = self::declarations();
                if (count($tools) >= config('agent.max_tools') - 1 || $step === config('agent.max_steps') - 1) {
                    $declarations = [end($declarations)];
                }
                $result = $this->transport->generate($contents, $declarations, $remaining);
                foreach ($result['usage'] ?? [] as $key => $value) {
                    if (in_array($key, ['promptTokenCount', 'candidatesTokenCount', 'totalTokenCount']) && is_numeric($value)) {
                        $usage[$key] = ($usage[$key] ?? 0) + max(0, (int) $value);
                    }
                }
                // Usage is retained even if cancellation or revocation arrived during the remote call.
                DB::table('agent_runs')->where('id', $id)->update(['usage' => json_encode($usage)]);
                $this->check($id);
                $content = $result['content'];
                $calls = array_values(array_filter($content['parts'] ?? [], fn ($p) => isset($p['functionCall'])));
                if (! $calls) {
                    throw new AgentFailure('invalid_response');
                }
                $contents[] = $content; // Preserve opaque signatures and function IDs verbatim.
                $responses = [];
                foreach ($calls as $part) {
                    $this->check($id);
                    if (count($tools) >= config('agent.max_tools')) {
                        throw new AgentFailure('tool_limit');
                    }
                    $call = $part['functionCall'];
                    $name = $call['name'] ?? '';
                    $args = $call['args'] ?? [];
                    if (! is_array($args)) {
                        throw new AgentFailure('invalid_tool');
                    }
                    $tools[] = in_array($name, ['search_experiences', 'get_experience', 'finish_answer']) ? $name : 'rejected';
                    DB::table('agent_runs')->where('id', $id)->update(['tools' => json_encode($tools), 'progress' => $name === 'finish_answer' ? 'Checking sources' : 'Reading published sources']);
                    $all = $this->sources->all($this->service->scope($conversation->slug));
                    if ($name === 'finish_answer') {
                        if (count($calls) !== 1) {
                            throw new AgentFailure('invalid_response');
                        }
                        $answer = $this->validateAnswer($args, $seen, $all);
                        $this->check($id);
                        DB::table('agent_runs')->where('id', $id)->where('state', 'running')
                            ->update(['state' => 'completed', 'progress' => 'Complete', 'answer' => json_encode($answer), 'finished_at' => now()->toIso8601String()]);

                        return;
                    }
                    $found = $this->sources->retrieve($all, $name, $args);
                    foreach ($found as $source) {
                        $seen[$source['handle']] = $source;
                    }
                    // A valid empty search still permits an explicit gap answer.
                    $seen['_searched'] = true;
                    $response = ['name' => $name, 'response' => ['sources' => $found, 'truncated' => count($found) >= 16, 'notice' => 'Only published fields; URLs have not been inspected.']];
                    if (isset($call['id'])) {
                        $response['id'] = $call['id'];
                    }
                    $responses[] = ['functionResponse' => $response];
                }
                $contents[] = ['role' => 'user', 'parts' => $responses];
            }
            throw new AgentFailure('step_limit');
        } catch (Throwable $error) {
            $reason = $error instanceof AgentFailure ? $error->reason : ($error instanceof DomainError ? 'scope_changed' : 'unavailable');
            DB::table('agent_runs')->where('id', $id)->where('state', 'running')->update([
                'state' => $reason === 'timeout' ? 'expired' : 'failed', 'progress' => 'Answer unavailable', 'error_code' => $reason, 'finished_at' => now()->toIso8601String(),
            ]);
        }
    }

    private function check(string $id): object
    {
        $row = DB::table('agent_runs')->where('id', $id)->first();
        if (! $row || $this->service->expire($row)->state !== 'running') {
            throw new AgentFailure('cancelled');
        }
        if (! $this->service->available()) {
            throw new AgentFailure('unavailable');
        }
        $conversation = $this->service->conversation($row->conversation_id);
        if (! $this->service->available($conversation->slug)) {
            throw new AgentFailure('unavailable');
        }

        return $row;
    }

    private function validateAnswer(array $args, array $seen, array $all): array
    {
        $validator = Validator::make($args, [
            'answer' => 'required|string|max:6000', 'insufficient_information' => 'required|boolean',
            'citations' => 'present|array|max:8', 'citations.*.handle' => 'required|string', 'citations.*.excerpt' => 'required|string|max:1000',
            'follow_up_questions' => 'sometimes|array|max:3', 'follow_up_questions.*' => 'required|string|max:200',
        ]);
        if ($validator->fails() || (! $args['insufficient_information'] && ! $args['citations'])) {
            throw new AgentFailure('invalid_response');
        }
        $citations = [];
        foreach ($args['citations'] as $citation) {
            $handle = $citation['handle'];
            $excerpt = $citation['excerpt'];
            if (! isset($all[$handle], $seen[$handle]) || ! str_contains($seen[$handle]['text'], $excerpt) || ! str_contains($all[$handle]['text'], $excerpt)) {
                throw new AgentFailure('invalid_citation');
            }
            $source = $all[$handle];
            $citations[] = array_intersect_key($source, array_flip(['handle', 'experience_id', 'title', 'version', 'field'])) + ['excerpt' => $excerpt];
        }

        return ['answer' => $args['answer'], 'citations' => $citations, 'insufficient_information' => (bool) $args['insufficient_information'], 'follow_up_questions' => $args['follow_up_questions'] ?? []];
    }
}
