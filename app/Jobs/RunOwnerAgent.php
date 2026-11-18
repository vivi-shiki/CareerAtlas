<?php

namespace App\Jobs;

use App\Agent\OwnerAgentRunner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

final class RunOwnerAgent implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 55;

    public function __construct(public string $runId) {}

    public function handle(OwnerAgentRunner $runner): void
    {
        $runner->execute($this->runId);
    }

    public function failed(?\Throwable $exception): void
    {
        DB::table('owner_ai_runs')->where('id', $this->runId)->whereIn('state', ['queued', 'running'])->update(['state' => 'failed', 'error_code' => 'worker_error', 'output' => null, 'updated_at' => now()]);
    }
}
