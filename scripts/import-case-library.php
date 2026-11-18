<?php

use App\Domain\OperationDispatcher;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

// This is an explicit development-data importer, never a deployment seeder.
$url = rtrim(config('app.url'), '/');
if (! $app->environment('local', 'testing') || ! in_array(parse_url($url, PHP_URL_HOST), ['localhost', '127.0.0.1', '::1'], true)) {
    throw new RuntimeException('Case imports require a local/test environment and loopback APP_URL.');
}
if (array_diff(array_slice($argv, 1), ['--publish-local'])) {
    throw new RuntimeException('Usage: php scripts/import-case-library.php [--publish-local]');
}
$publish = in_array('--publish-local', $argv, true);
$directory = base_path('.local/case-library');
if (! is_dir($directory) && ! mkdir($directory, 0700, true)) {
    throw new RuntimeException('Cannot create the private import directory.');
}
$lock = fopen($directory.'/import.lock', 'c');
if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
    throw new RuntimeException('Another case import is running.');
}
$manifestPath = $directory.'/manifest.json';
$manifest = is_file($manifestPath) ? json_decode(file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR) : [];
$connection = config('database.default');
$databaseIdentity = hash('sha256', json_encode([
    $connection, config("database.connections.$connection.host"),
    config("database.connections.$connection.port"), config("database.connections.$connection.database"),
]));
if ($manifest && $manifest['database_identity'] !== $databaseIdentity) {
    throw new RuntimeException('This manifest belongs to another database. Preserve it before importing elsewhere.');
}
$manifest += ['database_identity' => $databaseIdentity, 'cases' => []];
$save = function () use (&$manifest, $manifestPath) {
    $temporary = $manifestPath.'.tmp';
    if (file_put_contents($temporary, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL) === false) {
        throw new RuntimeException('Cannot persist the private manifest.');
    }
    chmod($temporary, 0600);
    if (! rename($temporary, $manifestPath)) {
        throw new RuntimeException('Cannot replace the private manifest.');
    }
};
foreach (['grace-hopper', 'alex-morgan'] as $key) {
    $raw = file_get_contents(base_path("examples/case-library/$key.json"));
    $case = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    $digest = hash('sha256', $raw);
    $existing = $manifest['cases'][$key] ?? null;
    if ($existing) {
        if ($existing['dataset_digest'] !== $digest || ! User::whereKey($existing['owner_id'])->where('email', $existing['email'])->exists()) {
            throw new RuntimeException('Dataset or identity changed; review it manually instead of overwriting the case.');
        }
    } else {
        $email = 'case-library-'.$key.'-v'.$case['version'].'@example.test';
        if (User::where('email', $email)->exists()) {
            throw new RuntimeException('Case identity already exists without a matching manifest; refusing to overwrite it.');
        }
        $existing = DB::transaction(function () use ($case, $digest, $email) {
            $password = Str::random(40);
            $user = User::create(['name' => $case['profile']['display_name'], 'email' => $email, 'password' => Hash::make($password)]);
            $op = fn ($name, $input = []) => app(OperationDispatcher::class)->execute($user, $name, $input);
            $profile = $op('get_profile');
            $op('update_profile', $case['profile'] + ['expected_version' => $profile['version']]);
            $sources = array_column($case['sources'], null, 'id');
            $items = [];
            $records = [];
            foreach ($case['projects'] as $projectData) {
                $project = $op('create_project', $projectData['project']);
                foreach ($projectData['experiences'] as $entry) {
                    $record = $op('create_experience', $entry['record'] + ['project_id' => $project['id']]);
                    foreach ($entry['skills'] as $name) {
                        $skill = $op('create_skill', ['name' => $name]);
                        $record = $op('attach_skill', ['id' => $record['id'], 'skill_id' => $skill['id'], 'expected_version' => $record['version']]);
                    }
                    $evidence = $entry['evidence'] ?? [];
                    foreach ($entry['source_ids'] ?? [] as $sourceId) {
                        $source = $sources[$sourceId];
                        $evidence[] = ['kind' => 'document', 'label' => $source['publisher'].': '.$source['title'], 'url' => $source['url']];
                    }
                    foreach ($evidence as $item) {
                        $record = $op('create_evidence', $item + ['experience_id' => $record['id'], 'expected_version' => $record['version']]);
                    }
                    if ($entry['confirm'] ?? true) {
                        $record = $op('confirm_experience', ['id' => $record['id'], 'expected_version' => $record['version']]);
                    }
                    $records[$entry['key']] = $record['id'];
                    if ($entry['publish'] ?? true) {
                        $items[] = ['experience_id' => $record['id'],
                            'fields' => ['title', 'record_type', 'occurrence', 'personal_action', 'result', 'validation', 'remaining_work', 'skills', 'evidence'],
                            'project_fields' => ['title', 'type', 'role', 'background', 'team_outcome', 'occurrence']];
                    }
                }
            }

            return ['owner_id' => $user->id, 'email' => $email, 'password' => $password, 'dataset_digest' => $digest,
                'display_name' => $case['profile']['display_name'], 'notice' => $case['notice'], 'records' => $records,
                'selection' => ['profile_fields' => ['display_name', 'direction', 'biography', 'preferred_language', 'contact_links'], 'items' => $items]];
        });
        $manifest['cases'][$key] = $existing;
        $save();
    }
    if ($publish && ! isset($existing['publication'])) {
        $user = User::findOrFail($existing['owner_id']);
        $op = fn ($name, $input) => app(OperationDispatcher::class)->execute($user, $name, $input);
        $preview = $op('preview_publication', $existing['selection']);
        $publication = $op('publish_publication', $existing['selection'] + ['preview_digest' => $preview['digest'], 'idempotency_key' => "case-library-$key-v".$case['version']]);
        $manifest['cases'][$key]['publication'] = ['id' => $publication['id'], 'slug' => $publication['slug']];
        $save();
    }
    echo $key.': '.count($existing['records']).' records; '.(isset($manifest['cases'][$key]['publication']) ? 'local publication recorded' : 'private only').PHP_EOL;
}

// Navigation contains only labels and public URLs. Credentials remain in the private manifest.
$escape = fn ($value) => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$html = '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>CareerAtlas case library</title><style>body{font:18px/1.65 system-ui;max-width:850px;margin:3rem auto;padding:0 1.5rem;color:#18323d;background:#f4f7f6}article{background:white;border:1px solid #cedcd7;border-radius:12px;padding:1.5rem;margin:1.5rem 0}a{color:#076656}small{display:block}</style><main><h1>CareerAtlas case library</h1><p>Local demonstration index. The application must be running. These links are not an Internet deployment. Public pages show selected snapshots, not the owner workspace.</p>';
foreach ($manifest['cases'] as $case) {
    $html .= '<article><h2>'.$escape($case['display_name']).'</h2><p>'.$escape($case['notice']).'</p>';
    if (isset($case['publication'])) {
        $html .= '<a href="'.$escape($url.'/p/'.$case['publication']['slug']).'">Open public portfolio</a>';
    } else {
        $html .= '<p>Private only: no public portfolio created.</p>';
    }
    $html .= '<small>Questions require separate Agent configuration. A recorded URL may be unavailable after revocation.</small></article>';
}
file_put_contents($directory.'/index.html', $html.'</main></html>');
echo 'Local index and private manifest saved. No credentials printed; no remote deployment or AI call performed.'.PHP_EOL;
