<?php

use App\Http\Controllers\AgentController;
use App\Http\Controllers\IdentityController;
use App\Http\Controllers\OperationController;
use App\Http\Middleware\NoStore;
use App\Services\PublicationProjection;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->middleware(NoStore::class)->group(function () {
    Route::get('/csrf', fn () => response()->json(['csrf_token' => csrf_token()]));
    Route::post('/auth/register', [IdentityController::class, 'register'])->middleware('throttle:10,1');
    Route::post('/auth/login', [IdentityController::class, 'login'])->middleware('throttle:10,1');
    Route::middleware('auth')->group(function () {
        Route::get('/auth/me', [IdentityController::class, 'me']);
        Route::post('/auth/logout', [IdentityController::class, 'logout']);
        Route::post('/operations/{operation}', OperationController::class)->middleware('throttle:180,1');
    });
    Route::get('/public/{slug}', fn (string $slug, PublicationProjection $p) => ['data' => $p->publicPortfolio($slug)]);
    Route::prefix('/public/{slug}/agent')->group(function () {
        Route::get('/', [AgentController::class, 'status']);
        Route::post('/conversations', [AgentController::class, 'create'])->middleware('throttle:60,1');
        Route::get('/conversations/{conversation}', [AgentController::class, 'history'])->whereUuid('conversation');
        Route::post('/conversations/{conversation}/runs', [AgentController::class, 'submit'])->whereUuid('conversation')->middleware('throttle:60,1');
        Route::get('/runs/{run}', [AgentController::class, 'read'])->whereUuid('run');
        Route::post('/runs/{run}/cancel', [AgentController::class, 'cancel'])->whereUuid('run');
    });
    Route::get('/public/{slug}/projects/{id}', fn (string $slug, string $id, PublicationProjection $p) => ['data' => $p->project($slug, $id)]);
    Route::get('/public/{slug}/experiences/{id}', fn (string $slug, string $id, PublicationProjection $p) => ['data' => $p->citation($slug, $id)]);
    Route::get('/public/{slug}/skills', fn (string $slug, PublicationProjection $p) => ['data' => $p->publicPortfolio($slug)['skills']]);
    Route::get('/public/{slug}/skills/{id}', fn (string $slug, string $id, PublicationProjection $p) => ['data' => $p->skill($slug, $id)]);
});
Route::view('/', 'app')->middleware(NoStore::class);
Route::view('/{path}', 'app')->where('path', '(login|register|profile|projects|experiences|skills|publish|capture|preparation|opportunities|p)(/.*)?')->middleware(NoStore::class);

if (app()->environment('local', 'testing')) {
    Route::view('/design/{screen}', 'app')->where('screen', 'editor|publication|portfolio|projects|skills|profile|login|p1-capture|p1-preparation|p1-opportunity');
}
