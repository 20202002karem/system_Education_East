<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use App\Exceptions\DomainConflictException;
use App\Services\IdempotencyService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Shared M1/M2-convention helpers for the M3 controllers (envelope, pagination, idempotent replay). */
trait M3Responses
{
    protected function paginated(Request $request, $query, callable $map): JsonResponse
    {
        $perPage = max(1, min((int) $request->query('per_page', 20), 100));
        $p = $query->paginate($perPage, ['*'], 'page', max(1, (int) $request->query('page', 1)));

        return ApiResponse::ok($map(collect($p->items())), 200, ['page' => $p->currentPage(), 'per_page' => $perPage, 'total' => $p->total()]);
    }

    /** Replays the first stored response for the same (user, Idempotency-Key); stores successful ones. */
    protected function idempotent(Request $request, callable $fn): JsonResponse
    {
        $idem = app(IdempotencyService::class);
        $actor = $request->user();
        $key = (string) $request->header('Idempotency-Key');
        if ($cached = $idem->find($actor->id, $key)) {
            return response()->json($cached->response_snapshot, $cached->response_status);
        }
        try {
            $response = $fn();
        } catch (DomainConflictException $e) {
            return ApiResponse::error($e->errorCode, $e->getMessage(), $e->status, $e->fields);
        }
        if ($response->getStatusCode() < 400) {
            $idem->remember($actor->id, $key, $response);
        }

        return $response;
    }

    protected function run(callable $fn): JsonResponse
    {
        try {
            return $fn();
        } catch (DomainConflictException $e) {
            return ApiResponse::error($e->errorCode, $e->getMessage(), $e->status, $e->fields);
        }
    }

    protected function like(string $term): string
    {
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';
    }
}
