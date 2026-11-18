<?php

namespace Tests\Fakes;

use App\Agent\AgentFailure;
use App\Agent\OwnerModelTransport;
use Illuminate\Support\Facades\DB;

final class OwnerModel implements OwnerModelTransport
{
    public string $fault = '';

    public string $runId = '';

    public array $requests = [];

    public function generate(array $contents, array $declarations, float $timeout, string $instructions): array
    {
        $this->requests[] = ['contents' => $contents, 'declarations' => $declarations];
        if ($this->fault === 'failure') {
            throw new AgentFailure('provider_error');
        }
        $brief = json_decode($contents[0]['parts'][0]['text'], true);
        if (str_contains($brief['question'] ?? '', '[test:failure]')) {
            throw new AgentFailure('provider_error');
        }
        if (str_contains($brief['question'] ?? '', '[test:slow]')) {
            usleep(2000000);
        }
        if (count($contents) === 1) {
            return $this->call('read_selected_sources', []);
        }
        if ($this->fault === 'cancel') {
            DB::table('owner_ai_runs')->where('id', $this->runId)->update(['state' => 'cancelled']);
        }
        $bundle = $contents[2]['parts'][0]['functionResponse']['response'];
        $sources = $bundle['sources'];
        $source = collect($sources)->firstWhere('field', 'personal_action') ?? $sources[0] ?? null;
        $base = ['questions' => [], 'insufficient_information' => ! $source, 'citations' => $source ? [['handle' => $source['handle'], 'excerpt' => $source['text']]] : []];
        $base += match ($brief['purpose']) {
            'capture' => ['draft' => ['title' => 'Keyboard test notes', 'personal_action' => $source['text'] ?? '', 'skill_suggestions' => []]],
            'mapping' => ['mapping' => [['requirement' => $brief['requirements'] ?? 'Review contribution', 'source_ids' => $source && $source['experience_id'] ? [$source['experience_id']] : [], 'reason' => 'Selected evidence describes the contribution.', 'gap' => 'No further facts are established.']]],
            'feedback' => ['feedback' => ['specificity' => 'Name the action you performed.', 'structure' => 'Explain context, action and validation.', 'consistency' => 'Keep the claim within the selected sources.', 'clarity' => 'Use concise sentences.']],
            'export' => ['translations' => array_map(fn ($field) => ['key' => $field['key'], 'text' => $field['text']], $bundle['fields'])],
            default => ['text' => $source['text'] ?? 'No selected evidence establishes an answer.'],
        };
        if ($this->fault === 'citation') {
            $base['citations'] = [['handle' => 'invented', 'excerpt' => 'invented']];
        }
        if ($this->fault === 'questions') {
            $base['questions'] = ['One?', 'Two?', 'Three?'];
        }

        return $this->call('finish_proposal', $base);
    }

    private function call(string $name, array $args): array
    {
        return ['content' => ['role' => 'model', 'parts' => [['functionCall' => ['name' => $name, 'args' => $args]]]]];
    }
}
