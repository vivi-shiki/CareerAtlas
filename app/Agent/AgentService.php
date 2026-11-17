<?php

namespace App\Agent;

use App\Domain\DomainError;
use App\Jobs\RunPortfolioAgent;
use App\Services\PublicationProjection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

final class AgentService
{
    public function available(?string $slug = null): bool
    {
        $allowlist = config('agent.publication_allowlist', '*');
        if ($slug !== null && $allowlist !== '*' && ! in_array($slug, array_filter(array_map('trim', explode(',', $allowlist))), true)) {
            return false;
        }

        return config('agent.enabled') && ((config('agent.driver') === 'gemini' && config('agent.key') && config('agent.model'))
            || (app()->environment('testing') && config('agent.driver') === 'fake'));
    }

    public function scope(string $slug): array
    {
        return app(PublicationProjection::class)->publicPortfolio($slug);
    }

    public function fingerprint(array $scope): string
    {
        return hash('sha256', json_encode($scope, JSON_THROW_ON_ERROR));
    }

    public function conversation(string $id, ?string $principal = null, ?string $slug = null): object
    {
        $row = DB::table('agent_conversations')->where('id', $id)->first();
        abort_unless($row && (! $principal || hash_equals($row->principal, $principal)) && (! $slug || $row->slug === $slug)
            && now()->lt($row->expires_at), 404);
        if (! hash_equals($row->scope_hash, $this->fingerprint($this->scope($row->slug)))) {
            throw new DomainError('scope_changed', 'Published content changed. Start a new conversation.', 409);
        }

        return $row;
    }

    public function create(string $slug, string $principal, string $ip): array
    {
        abort_unless($this->available($slug), 503);
        $scope = $this->scope($slug);
        $this->limit('conversation:'.$principal, 20, 3600);
        $this->limit('conversation-ip:'.$ip, 60, 3600);
        $id = (string) Str::uuid();
        DB::table('agent_conversations')->insert(['id' => $id, 'principal' => $principal, 'slug' => $slug,
            'scope_hash' => $this->fingerprint($scope), 'created_at' => now()->toIso8601String(), 'expires_at' => now()->addDay()->toIso8601String()]);

        return ['id' => $id, 'runs' => []];
    }

    public function history(string $id, string $principal, string $slug): array
    {
        $this->conversation($id, $principal, $slug);
        $rows = DB::table('agent_runs')->where('conversation_id', $id)->orderByDesc('created_at')->limit(10)->get()->reverse();

        return ['id' => $id, 'runs' => $rows->map(fn ($r) => $this->present($this->expire($r)))->values()->all()];
    }

    public function submit(string $id, string $principal, string $slug, string $ip, array $input): array
    {
        abort_unless($this->available($slug), 503);
        $this->conversation($id, $principal, $slug);

        return DB::transaction(function () use ($id, $principal, $ip, $input) {
            DB::table('agent_conversations')->where('id', $id)->lockForUpdate()->first();
            $existing = DB::table('agent_runs')->where('conversation_id', $id)->where('request_id', $input['request_id'])->first();
            if ($existing) {
                if ($existing->question !== $input['question']) {
                    throw new DomainError('idempotency_conflict', 'This request ID was already used for another question.');
                }

                return $this->present($this->expire($existing));
            }
            foreach (DB::table('agent_runs')->where('conversation_id', $id)->whereIn('state', ['queued', 'running'])->get() as $active) {
                if (in_array($this->expire($active)->state, ['queued', 'running'])) {
                    throw new DomainError('run_active', 'Wait for or cancel the current answer.');
                }
            }
            $this->limit('questions:'.$principal, config('agent.session_limit'), 600);
            $this->limit('questions-ip:'.$ip, config('agent.ip_limit'), 3600);
            $run = (string) Str::uuid();
            DB::table('agent_runs')->insert(['id' => $run, 'conversation_id' => $id, 'request_id' => $input['request_id'],
                'question' => $input['question'], 'created_at' => now()->toIso8601String(), 'deadline_at' => now()->addSeconds(config('agent.deadline_seconds'))->toIso8601String()]);
            RunPortfolioAgent::dispatch($run)->onConnection('database')->onQueue('agent')->afterCommit();

            return $this->present(DB::table('agent_runs')->where('id', $run)->first());
        });
    }

    public function run(string $id, string $principal, string $slug, bool $cancel = false): array
    {
        $row = DB::table('agent_runs')->where('id', $id)->first();
        abort_unless($row, 404);
        $this->conversation($row->conversation_id, $principal, $slug);
        if ($cancel) {
            DB::table('agent_runs')->where('id', $id)->whereIn('state', ['queued', 'running'])
                ->update(['state' => 'cancelled', 'progress' => 'Cancelled', 'finished_at' => now()->toIso8601String()]);
            $row = DB::table('agent_runs')->where('id', $id)->first();
        }

        return $this->present($this->expire($row));
    }

    public function expire(object $row): object
    {
        if (in_array($row->state, ['queued', 'running']) && now()->gte($row->deadline_at)) {
            DB::table('agent_runs')->where('id', $row->id)->whereIn('state', ['queued', 'running'])
                ->update(['state' => 'expired', 'progress' => 'Timed out', 'error_code' => 'timeout', 'finished_at' => now()->toIso8601String()]);

            return DB::table('agent_runs')->where('id', $row->id)->first();
        }

        return $row;
    }

    public function present(object $row): array
    {
        return ['id' => $row->id, 'question' => $row->question, 'state' => $row->state, 'progress' => $row->progress,
            'answer' => $row->state === 'completed' ? json_decode($row->answer, true) : null,
            'error_code' => $row->error_code, 'usage' => json_decode($row->usage, true), 'created_at' => $row->created_at];
    }

    private function limit(string $key, int $max, int $seconds): void
    {
        $key = 'agent:'.hash('sha256', $key);
        if (RateLimiter::tooManyAttempts($key, $max)) {
            throw new DomainError('rate_limited', 'Question limit reached. Please try again later.', 429);
        }
        RateLimiter::hit($key, $seconds);
    }
}
