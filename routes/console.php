<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

Artisan::command('deploy:check', function () {
    $checks = [
        'production environment' => app()->environment('production'),
        'debug disabled' => ! config('app.debug'),
        'HTTPS application URL' => str_starts_with(config('app.url', ''), 'https://'),
        'application key configured' => filled(config('app.key')),
        'secure session cookie' => config('session.secure') === true,
        'persistent sessions' => config('session.driver') === 'database',
        'persistent cache' => in_array(config('cache.default'), ['file', 'database', 'redis'], true),
        'PostgreSQL selected' => config('database.default') === 'pgsql',
        'production model driver' => config('agent.driver') !== 'fake',
        'model configuration' => ! config('agent.enabled') || (filled(config('agent.key')) && filled(config('agent.model'))),
    ];
    foreach ($checks as $label => $passed) {
        if (! $passed) {
            $this->error('Deployment check failed: '.$label);

            return 1;
        }
    }
    foreach (['users', 'sessions', 'jobs', 'projects', 'owner_ai_runs'] as $table) {
        if (! Schema::hasTable($table)) {
            $this->error('Deployment check failed: required database schema');

            return 1;
        }
    }
    $this->info('Production configuration and database checks passed.');

    return 0;
})->purpose('Check production readiness without printing secrets or changing records');

Artisan::command('agent:prune', function () {
    $count = DB::table('agent_conversations')->where('expires_at', '<=', now()->toIso8601String())->delete();
    $this->info('Expired conversations removed: '.$count);
});
Schedule::command('agent:prune')->daily();

Artisan::command('practice:prune', function () {
    $count = DB::table('practice_sessions')->where('retained', false)->where('expires_at', '<=', now()->toIso8601String())->delete();
    $this->info('Expired practice sessions removed: '.$count);
});
Schedule::command('practice:prune')->daily();

Artisan::command('owner-ai:prune', function () {
    $count = DB::table('owner_ai_runs')->where('created_at', '<=', now()->subDay()->toIso8601String())->delete();
    $this->info('Expired draft runs removed: '.$count);
});
Schedule::command('owner-ai:prune')->daily();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
