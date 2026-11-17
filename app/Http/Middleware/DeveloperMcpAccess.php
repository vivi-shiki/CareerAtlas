<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

final class DeveloperMcpAccess
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->bearerToken(), 401);
        $token = $request->user()?->currentAccessToken();
        abort_unless($token instanceof PersonalAccessToken && $token->can('career:manage'), 403);
        if ($request->header('Origin')) {
            abort_unless(rtrim($request->header('Origin'), '/') === rtrim(config('app.url'), '/'), 403);
        }

        return $next($request);
    }
}
