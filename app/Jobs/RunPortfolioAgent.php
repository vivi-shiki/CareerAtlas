<?php

namespace App\Jobs;

use App\Agent\AgentRunner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

final class RunPortfolioAgent implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 55;

    public function __construct(public string $runId) {}

    public function handle(AgentRunner $runner): void
    {
        $runner->execute($this->runId);
    }

    public function failed(?\Throwable $exception): void
    {
        DB::table('agent_runs')->where('id', $this->runId)->whereIn('state', ['queued', 'running'])
            ->update(['state' => 'failed', 'progress' => 'Answer unavailable', 'error_code' => 'worker_error', 'finished_at' => now()->toIso8601String()]);
    }
}
