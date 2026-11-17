<?php

use App\Domain\DomainError;
use App\Support\Localize;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->remove(ConvertEmptyStringsToNull::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $r) => $r->is('api/*', 'mcp/*') || $r->expectsJson());
        $exceptions->render(function (DomainError $e, Request $r) {
            return response()->json(['code' => $e->errorCode, 'message' => Localize::message($e->getMessage(), $r), 'message_key' => $e->getMessage()], $e->status);
        });
        $exceptions->render(function (ValidationException $e, Request $r) {
            return response()->json(['code' => 'validation_error', 'message' => Localize::message('Review the highlighted fields.', $r), 'message_key' => 'Review the highlighted fields.', 'errors' => array_map(fn ($messages) => array_map(fn ($message) => Localize::message($message, $r), $messages), $e->errors()), 'error_keys' => $e->errors()], 422);
        });
        $exceptions->render(function (HttpExceptionInterface $e, Request $r) {
            if ($e->getStatusCode() === 404) {
                if ($r->isMethod('GET') && ! $r->is('api/*', 'mcp/*') && ! $r->expectsJson()) {
                    return response()->view('app', [], 404)->header('Cache-Control', 'no-store, private');
                }

                return response()->json(['code' => 'unavailable', 'message' => Localize::message('The requested resource is unavailable.', $r), 'message_key' => 'The requested resource is unavailable.'], 404);
            }
        });
    })->create();
