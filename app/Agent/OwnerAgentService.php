<?php

namespace App\Agent;

use App\Domain\CaptureService;
use App\Domain\DomainError;
use App\Domain\OpportunityService;
use App\Domain\PreparationService;
use App\Domain\Store;
use App\Jobs\RunOwnerAgent;
use Illuminate\Support\Facades\DB;

final class OwnerAgentService
{
    public function __construct(private OwnerContext $context) {}

    public function available(int $owner): bool
    {
        $allowed = config('agent.owner_allowlist', '');

        return ($allowed === '*' || in_array((string) $owner, array_map('trim', explode(',', $allowed)), true)) && app(AgentService::class)->available();
    }

    public function start(int $owner, array $input): array
    {
        $hash = $this->context->digest($input);
        $existing = DB::table('owner_ai_runs')->where('owner_id', $owner)->where('request_id', $input['request_id'])->first();
        if ($existing) {
            if (! hash_equals($existing->request_hash, $hash)) {
                throw new DomainError('idempotency_conflict', 'This request key was used with different input.');
            }

            return $this->read($owner, $existing->id);
        }
        if (! $this->available($owner)) {
            throw new DomainError('unavailable', 'AI drafting is unavailable. You can keep editing manually.', 503);
        }
        DB::table('owner_ai_runs')->where('owner_id', $owner)->whereIn('state', ['queued', 'running'])->where('deadline_at', '<=', now()->toIso8601String())->update(['state' => 'expired', 'output' => null, 'error_code' => 'timeout']);
        if (DB::table('owner_ai_runs')->where('owner_id', $owner)->whereIn('state', ['queued', 'running'])->exists()) {
            throw new DomainError('run_active', 'Wait for or cancel the current draft.');
        }
        if (DB::table('owner_ai_runs')->where('owner_id', $owner)->where('created_at', '>', now()->subHour()->toIso8601String())->count() >= 30) {
            throw new DomainError('rate_limited', 'Draft limit reached. Please try again later.', 429);
        }
        $context = $this->context->build($owner, $input);
        $row = Store::insert('owner_ai_runs', ['owner_id' => $owner, 'purpose' => $input['purpose'], 'subject_id' => $input['subject_id'], 'subject_version' => $input['expected_version'], 'request_id' => $input['request_id'], 'request_hash' => $hash, 'context' => $context, 'context_digest' => $this->context->digest($context), 'deadline_at' => now()->addSeconds(45)]);
        RunOwnerAgent::dispatch($row['id'])->onQueue('agent')->afterCommit();

        return $this->view($row);
    }

    public function current(array $run): bool
    {
        try {
            return hash_equals($run['context_digest'], $this->context->digest($this->context->build($run['owner_id'], $run['context']['request'])));
        } catch (\Throwable) {
            return false;
        }
    }

    public function read(int $owner, string $id, bool $cancel = false): array
    {
        $row = Store::owned('owner_ai_runs', $id, $owner);
        if (in_array($row['state'], ['queued', 'running', 'completed'], true)) {
            if ($cancel) {
                $row = Store::update('owner_ai_runs', $id, ['state' => 'cancelled', 'output' => null]);
            } elseif (in_array($row['state'], ['queued', 'running'], true) && now()->gte($row['deadline_at'])) {
                $row = Store::update('owner_ai_runs', $id, ['state' => 'expired', 'output' => null, 'error_code' => 'timeout']);
            } elseif (! $this->current($row)) {
                $row = Store::update('owner_ai_runs', $id, ['state' => 'failed', 'output' => null, 'error_code' => 'source_changed']);
            }
        }

        return $this->view($row);
    }

    private function view(array $row): array
    {
        $view = array_intersect_key($row, array_flip(['id', 'state', 'purpose', 'subject_id', 'output', 'error_code', 'deadline_at']));
        if ($row['state'] === 'applied') {
            $view['output'] = null;
        }

        return $view;
    }

    public function apply(int $owner, string $id): array
    {
        $run = Store::owned('owner_ai_runs', $id, $owner);
        // Applied drafts are returned only while the original subject still belongs to the owner.
        if ($run['state'] === 'applied') {
            $table = match ($run['context']['subject_kind']) {
                'capture' => 'capture_drafts','practice' => 'practice_sessions','opportunity' => 'opportunities',default => 'preparations'
            };
            Store::owned($table, $run['subject_id'], $owner);

            return $run['application_result'];
        }
        $view = $this->read($owner, $id);
        if ($view['state'] !== 'completed' || ! $this->current($run)) {
            throw new DomainError('source_changed', 'Review a current completed draft before applying.');
        }
        $output = $run['output'];
        $context = $run['context'];
        $subject = null;
        if ($run['purpose'] === 'capture') {
            $subject = app(CaptureService::class)->save($owner, ['id' => $run['subject_id'], 'expected_version' => $run['subject_version'], 'draft' => $output['draft']]);
            $subject = Store::update('capture_drafts', $subject['id'], ['provenance' => 'ai_assisted', 'questions' => $output['questions']]);
        } elseif ($run['purpose'] === 'answer') {
            $subject = app(PreparationService::class)->saveAnswer($owner, ['preparation_id' => $run['subject_id'], 'question' => $context['question'], 'answer' => $output['text']]);
        } elseif ($run['purpose'] === 'introduction') {
            $subject = app(OpportunityService::class)->save($owner, ['id' => $run['subject_id'], 'expected_version' => $run['subject_version'], 'introduction' => $output['text']]);
        } elseif ($run['purpose'] === 'feedback') {
            $practice = app(PreparationService::class)->practice($owner, $run['subject_id']);
            $attempts = $practice['attempts'];
            $attempts[$practice['current_index']]['feedback'] = $output['feedback'];
            $subject = Store::update('practice_sessions', $practice['id'], ['attempts' => $attempts, 'version' => $practice['version'] + 1]);
        }
        $result = ['subject' => $subject, 'output' => $output, 'export_digest' => $context['export_digest'] ?? null];
        Store::update('owner_ai_runs', $id, ['state' => 'applied', 'application_result' => $result]);

        return $result;
    }
}
