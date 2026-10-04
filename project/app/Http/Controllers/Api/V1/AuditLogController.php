<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditChainCheck;
use App\Models\AuditLog;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

/**
 * Batch 3 §2.5 — read-only. No route exists anywhere for PATCH/PUT/DELETE on
 * audit_log (IN-12). Chairman only in M1 (P-07 keeps secretary/engineer closed).
 */
class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min((int) $request->query('per_page', 20), 100);
        $query = AuditLog::query();

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->query('entity_type'));
        }
        if ($request->filled('entity_id')) {
            $query->where('entity_id', $request->query('entity_id'));
        }
        if ($request->filled('actor_id')) {
            $query->where('actor_id', $request->query('actor_id'));
        }
        if ($request->filled('from')) {
            $query->where('occurred_at', '>=', $request->query('from'));
        }
        if ($request->filled('to')) {
            $query->where('occurred_at', '<=', $request->query('to'));
        }

        $paginator = $query->orderByDesc('seq')->paginate($perPage, ['*'], 'page', (int) $request->query('page', 1));

        return ApiResponse::ok(
            $paginator->items(), 200,
            ['page' => $paginator->currentPage(), 'per_page' => $perPage, 'total' => $paginator->total()]
        );
    }

    public function chainChecks(Request $request)
    {
        $perPage = min((int) $request->query('per_page', 20), 100);
        $paginator = AuditChainCheck::orderByDesc('run_at')->paginate($perPage);

        return ApiResponse::ok(
            $paginator->items(), 200,
            ['page' => $paginator->currentPage(), 'per_page' => $perPage, 'total' => $paginator->total()]
        );
    }
}
