<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\GrantPermissionRequest;
use App\Models\PermissionGrant;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\IdempotencyService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

/**
 * Batch 3 §3.4 — AUTH-08. New row per grant; revoke closes the row.
 */
class PermissionGrantController extends Controller
{
    public function __construct(
        protected AuditLogger $audit,
        protected IdempotencyService $idempotency,
    ) {}

    public function store(GrantPermissionRequest $request, User $user)
    {
        $idKey = $request->header('Idempotency-Key');
        $actor = $request->user();

        if ($cached = $this->idempotency->find($actor->id, $idKey)) {
            return response()->json($cached->response_snapshot, $cached->response_status);
        }

        $grant = PermissionGrant::create([
            'user_id' => $user->id,
            'permission_key' => $request->input('permission_key'),
            'granted_by' => $actor->id,
            'granted_at' => now('UTC'),
            'reason' => $request->input('reason'),
        ]);

        $this->audit->record(
            actorId: $actor->id,
            action: 'permission.granted',
            entityType: 'permission_grant',
            entityId: $grant->id,
            after: ['user_id' => $user->id, 'permission_key' => $grant->permission_key],
            reason: $grant->reason,
        );

        $response = ApiResponse::created([
            'id' => $grant->id,
            'user_id' => $grant->user_id,
            'permission_key' => $grant->permission_key,
            'granted_by' => $grant->granted_by,
            'granted_at' => $grant->granted_at->toIso8601String(),
            'revoked_at' => null,
        ]);
        $this->idempotency->remember($actor->id, $idKey, $response);

        return $response;
    }

    public function revoke(Request $request, PermissionGrant $permissionGrant)
    {
        $actor = $request->user();

        if ($permissionGrant->revoked_at) {
            return ApiResponse::error('already_revoked', 'الصلاحية مسحوبة مسبقاً', 409);
        }

        $permissionGrant->update([
            'revoked_by' => $actor->id,
            'revoked_at' => now('UTC'),
            'reason' => $request->input('reason', $permissionGrant->reason),
        ]);

        $this->audit->record(
            actorId: $actor->id,
            action: 'permission.revoked',
            entityType: 'permission_grant',
            entityId: $permissionGrant->id,
            reason: $request->input('reason'),
        );

        return ApiResponse::ok($permissionGrant->fresh());
    }
}
