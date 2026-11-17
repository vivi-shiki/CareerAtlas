<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('agent:prune', function () {
    $count = DB::table('agent_conversations')->where('expires_at', '<=', now()->toIso8601String())->delete();
    $this->info('Expired conversations removed: '.$count);
});
Schedule::command('agent:prune')->daily();

Artisan::command('practice:prune', function () {
    $count = DB::table('practice_sessions')->where('retained', false)->where('expires_at', '<=', now())->delete();
    $this->info('Expired practice sessions removed: '.$count);
});
Schedule::command('practice:prune')->daily();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
