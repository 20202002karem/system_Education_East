<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

/**
 * Batch 3 §1 — response envelope conventions.
 * Success: {"data": {...}} or {"data": [...], "meta": {...}}
 * Error:   {"error": {"code","message","fields"}}
 */
class ApiResponse
{
    public static function ok(mixed $data, int $status = 200, array $meta = []): JsonResponse
    {
        $payload = ['data' => $data];
        if (! empty($meta)) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    public static function created(mixed $data): JsonResponse
    {
        return self::ok($data, 201);
    }

    public static function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    public static function error(string $code, string $message, int $status, array $fields = []): JsonResponse
    {
        $error = ['code' => $code, 'message' => $message];
        if (! empty($fields)) {
            $error['fields'] = $fields;
        }

        return response()->json(['error' => $error], $status);
    }
}
