<?php

// Read-only database observations for fictional local browser acceptance owners.
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('testing') || config('database.connections.pgsql.database') !== 'careeratlas_e2e') {
    throw new RuntimeException('Observations require the dedicated E2E database in testing.');
}
$input = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
$email = $input['email'] ?? '';
if (! preg_match('/^(browser|ui)-[a-f0-9-]+@example\.test$/', $email)) {
    throw new RuntimeException('Only synthetic browser owners are supported.');
}
$owner = DB::table('users')->where('email', $email)->first();
if (! $owner) {
    throw new RuntimeException('Synthetic owner unavailable.');
}
$experiences = DB::table('experiences')->where('owner_id', $owner->id)->pluck('id');
$publications = DB::table('publications')->where('owner_id', $owner->id)->pluck('id');
echo json_encode([
    'projects' => DB::table('projects')->where('owner_id', $owner->id)->count(),
    'experiences' => $experiences->count(),
    'record_versions' => DB::table('record_versions')->whereIn('experience_id', $experiences)->count(),
    'publications' => $publications->count(),
    'publication_snapshots' => DB::table('publication_snapshots')->whereIn('publication_id', $publications)->count(),
    'skill_associations' => DB::table('experience_skill')->whereIn('experience_id', $experiences)->count(),
    'evidence' => DB::table('evidence')->whereIn('experience_id', $experiences)->count(),
], JSON_THROW_ON_ERROR);
