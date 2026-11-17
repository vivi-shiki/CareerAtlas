<?php

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('local', 'testing')) {
    throw new RuntimeException('Acceptance fixtures require a local/test environment.');
}
$url = config('app.url');
if (! in_array(parse_url($url, PHP_URL_HOST), ['localhost', '127.0.0.1', '::1'], true)) {
    throw new RuntimeException('Acceptance credentials are restricted to the local application.');
}
$user = User::create([
    'email' => 'mcp-'.Str::lower(Str::random(12)).'@example.test',
    'name' => 'Alex Morgan (fictional)',
    'password' => Hash::make(Str::random(48)),
]);
$token = $user->createToken('mcp-acceptance', ['career:manage'], now()->addHours(8))->plainTextToken;
$path = base_path('.local/mcp-acceptance.json');
file_put_contents($path, json_encode(['url' => $url.'/mcp/career', 'token' => $token], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
chmod($path, 0600);
echo "Prepared a new fictional MCP identity and local-only credential file.\n";
