<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

/** BR-M3-16 / DD-M3-04 — internal notifications only (no email, push or SMS). */
class NotificationService
{
    /** @param array<int|null> $recipientIds */
    public function send(array $recipientIds, string $event, string $sourceType, int $sourceId, string $message, ?int $exceptUserId = null): void
    {
        $ids = collect($recipientIds)->filter()->unique()->reject(fn ($id) => $id === $exceptUserId)->values();
        foreach (User::whereIn('id', $ids)->where('status', 'active')->pluck('id') as $id) {
            Notification::create([
                'recipient_id' => $id, 'event_type' => $event, 'source_type' => $sourceType,
                'source_id' => $sourceId, 'message' => mb_substr($message, 0, 255),
            ]);
        }
    }

    /** chairman + secretary (workflow desk). */
    public function desk(): array
    {
        return User::whereIn('role', ['chairman', 'secretary'])->where('status', 'active')->pluck('id')->all();
    }
}
