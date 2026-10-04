<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;

/**
 * Batch 2 §B8 / M1 criterion 5. Append-only, hash-chained audit log.
 * This is the ONLY place in the codebase allowed to write to audit_log.
 * Never pass secrets (passwords, mfa_secret, tokens) in $before/$after/$reason —
 * callers are responsible for redacting sensitive attributes before calling.
 */
class AuditLogger
{
    /**
     * @param  int  $actorId
     * @param  string  $action  e.g. "user.created", "permission.granted"
     * @param  string  $entityType  e.g. "user", "site"
     * @param  int|string  $entityId
     * @param  array|null  $before
     * @param  array|null  $after
     * @param  string|null  $reason
     * @param  array  $flags
     */
    public function record(
        int $actorId,
        string $action,
        string $entityType,
        int|string $entityId,
        ?array $before = null,
        ?array $after = null,
        ?string $reason = null,
        array $flags = [],
        string $source = 'web',
        ?string $ip = null,
    ): AuditLog {
        return DB::transaction(function () use ($actorId, $action, $entityType, $entityId, $before, $after, $reason, $flags, $source, $ip) {
            // Lock the last row so the hash chain cannot race under concurrent writes.
            $last = AuditLog::query()->lockForUpdate()->orderByDesc('seq')->first();
            $prevHash = $last?->hash ?? str_repeat('0', 64);

            $occurredAt = now('UTC');
            $payload = [
                'occurred_at' => $occurredAt->toIso8601String(),
                'actor_id' => $actorId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => (string) $entityId,
                'before' => $before,
                'after' => $after,
                'reason' => $reason,
                'source' => $source,
                'ip' => $ip,
                'flags' => $flags,
                'prev_hash' => $prevHash,
            ];

            $hash = hash('sha256', $prevHash.json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return AuditLog::create([
                'occurred_at' => $occurredAt,
                'actor_id' => $actorId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => (string) $entityId,
                'before' => $before,
                'after' => $after,
                'reason' => $reason,
                'source' => $source,
                'ip' => $ip,
                'flags' => $flags,
                'prev_hash' => $prevHash,
                'hash' => $hash,
            ]);
        });
    }

    /**
     * M1 criterion 5 — daily chain verification. Walks audit_log in seq order
     * and recomputes each hash from prev_hash + payload to detect tampering.
     */
    public function verifyChain(): array
    {
        $broken = null;
        $prevHash = str_repeat('0', 64);
        $fromSeq = null;
        $toSeq = null;

        AuditLog::query()->orderBy('seq')->chunk(500, function ($chunk) use (&$broken, &$prevHash, &$fromSeq, &$toSeq) {
            foreach ($chunk as $row) {
                $fromSeq ??= $row->seq;
                $toSeq = $row->seq;

                if ($row->prev_hash !== $prevHash) {
                    $broken ??= $row->seq;
                }

                $payload = [
                    'occurred_at' => $row->occurred_at->toIso8601String(),
                    'actor_id' => $row->actor_id,
                    'action' => $row->action,
                    'entity_type' => $row->entity_type,
                    'entity_id' => $row->entity_id,
                    'before' => $row->before,
                    'after' => $row->after,
                    'reason' => $row->reason,
                    'source' => $row->source,
                    'ip' => $row->ip,
                    'flags' => $row->flags,
                    'prev_hash' => $row->prev_hash,
                ];
                $expectedHash = hash('sha256', $row->prev_hash.json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

                if ($expectedHash !== $row->hash) {
                    $broken ??= $row->seq;
                }

                $prevHash = $row->hash;
            }
        });

        return [
            'result' => $broken ? 'broken' : 'ok',
            'from_seq' => $fromSeq ?? 0,
            'to_seq' => $toSeq ?? 0,
            'broken_at_seq' => $broken,
        ];
    }
}
