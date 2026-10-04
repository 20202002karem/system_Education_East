<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;

/**
 * Batch 3 §1/§6 — Idempotency-Key header is required on every POST that
 * creates a resource. Missing header → 400 idempotency_key_required.
 * Actual replay logic lives in App\Services\IdempotencyService and is invoked
 * by the controllers that create resources (so GET/action-only POSTs that
 * don't create a new resource are excluded via route middleware selection).
 */
class EnsureIdempotencyKey
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->hasHeader('Idempotency-Key') || trim((string) $request->header('Idempotency-Key')) === '') {
            return ApiResponse::error('idempotency_key_required', 'رأس Idempotency-Key مطلوب', 400);
        }

        return $next($request);
    }
}
