<?php

namespace App\Exceptions;

use App\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Central mapping to the Batch 3 §1 error envelope:
 * {"error": {"code","message","fields"}} with the status codes listed there.
 * Registered from bootstrap/app.php via ->withExceptions().
 */
class ApiExceptionRenderer
{
    public static function render(Throwable $e, Request $request): ?\Illuminate\Http\JsonResponse
    {
        if (! $request->is('api/*')) {
            return null;
        }

        if ($e instanceof ValidationException) {
            return ApiResponse::error('validation_failed', 'بيانات غير صالحة', 422, $e->errors());
        }

        if ($e instanceof AuthenticationException) {
            return ApiResponse::error('unauthenticated', 'غير مُصادَق', 401);
        }

        if ($e instanceof AuthorizationException) {
            return ApiResponse::error('forbidden', 'لا صلاحية لتنفيذ هذا الإجراء', 403);
        }

        // IN-08 / E-10: a resource outside the requester's scope returns 404,
        // never 403, to avoid leaking existence. Route-model-binding misses
        // and explicit scope checks both funnel through this path.
        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            return ApiResponse::error('not_found', 'المورد غير موجود', 404);
        }

        return null; // fall back to Laravel's default handling for anything unmapped
    }
}
