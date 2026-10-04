<?php

namespace App\Services;

use App\Models\IdempotencyKey;
use Illuminate\Http\JsonResponse;

/**
 * IN-11 / M1 criterion 6. Idempotency-Key header is required on every
 * resource-creating POST (Batch 3 §1). Same key replays the first response
 * instead of re-executing the action.
 */
class IdempotencyService
{
    public function find(int $userId, string $key): ?IdempotencyKey
    {
        return IdempotencyKey::query()->where('user_id', $userId)->where('key', $key)->first();
    }

    public function remember(int $userId, string $key, JsonResponse $response): void
    {
        IdempotencyKey::create([
            'user_id' => $userId,
            'key' => $key,
            'response_snapshot' => $response->getData(true),
            'response_status' => $response->getStatusCode(),
            'created_at' => now('UTC'),
        ]);
    }
}
