<?php

namespace App\Http\Middleware;

use App\Models\Session as SessionModel;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;

/**
 * M1 criterion 8 (MD-01) — 30 minute inactivity expiry, plus absolute token
 * TTL. Runs after Sanctum's auth:sanctum middleware. Requires the current
 * access token to have a matching `sessions` row (Batch 2 §B6).
 */
class TrackSessionActivity
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if (! $user || ! $token) {
            return ApiResponse::error('unauthenticated', 'غير مُصادَق', 401);
        }

        $session = SessionModel::find((string) $token->id);

        if (! $session) {
            return ApiResponse::error('unauthenticated', 'غير مُصادَق', 401);
        }

        $now = now('UTC');
        $inactivityLimit = (int) (\App\Models\Setting::find('session_inactivity_minutes')?->value ?? config('m1.auth.inactivity_minutes'));

        if ($now->greaterThan($session->expires_at)
            || $now->diffInMinutes($session->last_activity_at) > $inactivityLimit) {
            $session->delete();
            $token->delete();

            return ApiResponse::error('session_expired', 'انتهت صلاحية الجلسة بسبب الخمول', 401);
        }

        $session->update(['last_activity_at' => $now]);

        return $next($request);
    }
}
