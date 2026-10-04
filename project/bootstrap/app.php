<?php

use App\Exceptions\ApiExceptionRenderer;
use App\Http\Middleware\EnsureIdempotencyKey;
use App\Http\Middleware\RequireRole;
use App\Http\Middleware\TrackSessionActivity;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'idempotency' => EnsureIdempotencyKey::class,
            'session.activity' => TrackSessionActivity::class,
            'role' => RequireRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, $request) {
            return ApiExceptionRenderer::render($e, $request);
        });
    })->create();
