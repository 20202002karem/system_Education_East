<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;

/**
 * Role gate. Usage: ->middleware('role:chairman'). Scope/individual-permission
 * checks are layered on top inside the controller/FormRequest where a
 * finer-grained decision is needed (see App\Support\AccessPolicy).
 */
class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            return ApiResponse::error('forbidden', 'لا صلاحية لتنفيذ هذا الإجراء', 403);
        }

        return $next($request);
    }
}
