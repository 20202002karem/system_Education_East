<?php

use App\Http\Controllers\Api\V1\AssetController;
use App\Http\Controllers\Api\V1\AttachmentController;
use App\Http\Controllers\Api\V1\DraftController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ProposalController;
use App\Http\Controllers\Api\V1\RequestActionsController;
use App\Http\Controllers\Api\V1\RequestController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PermissionGrantController;
use App\Http\Controllers\Api\V1\ReferenceDataController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\SiteController;
use App\Http\Controllers\Api\V1\SiteManagerController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| M1 API routes — Batch 3 §2 Endpoint Inventory, verbatim.
| Base path /api/v1. Everything except /auth/login and /auth/mfa/verify
| requires auth:sanctum + session.activity (30-min inactivity / TTL).
| Everything except Auth is chairman-only in M1 (Authorization Matrix §4) —
| enforced via role:chairman on the route group, not left to controllers.
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // 2.1 Auth
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/mfa/verify', [AuthController::class, 'mfaVerify']);

    // M3 — temporary signed attachment link (signature is the credential; issued by M3-API-031).
    Route::get('/attachments/{attachment}/file', [AttachmentController::class, 'file'])->name('attachments.file')->whereNumber('attachment');

    Route::middleware(['auth:sanctum', 'session.activity'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // M2 — Assets (Batch 3 API-AST-01..09). Authorization lives in AssetAccess /
        // the Form Requests (reads: per view_scope; writes: chairman or edit_assets).
        Route::get('/assets', [AssetController::class, 'index']);
        Route::post('/assets', [AssetController::class, 'store'])->middleware('idempotency');
        Route::get('/assets/{asset}', [AssetController::class, 'show']);
        Route::patch('/assets/{asset}', [AssetController::class, 'update']);
        Route::post('/assets/{asset}/status-changes', [AssetController::class, 'changeStatus'])->middleware('idempotency');
        Route::get('/assets/{asset}/status-history', [AssetController::class, 'statusHistory']);
        Route::post('/assets/{asset}/identifier-corrections', [AssetController::class, 'correctIdentifier'])->middleware('idempotency');
        Route::get('/assets/{asset}/identifier-corrections', [AssetController::class, 'identifierCorrections']);
        Route::get('/assets/{asset}/legacy-numbers', [AssetController::class, 'legacyNumbers']);


        // BASELINE CHANGE (M3, read-only lookups; writes stay chairman-only): the M3 forms need
        // reference lists, sites and assignable users. Scope/field limits are enforced in the controllers.
        Route::get('/reference/{resource}', [ReferenceDataController::class, 'index'])
            ->whereIn('resource', ['device-categories', 'request-types', 'task-types', 'intake-channels']);
        Route::get('/sites', [SiteController::class, 'index']);
        Route::get('/sites/{site}', [SiteController::class, 'show']);
        Route::get('/users', [UserController::class, 'index']);

        // ---- M3 — Requests & Tasks (Batch 3, M3-API-001..037). Role gates here; scope/authority in services. ----
        Route::get('/requests', [RequestController::class, 'index']);
        Route::post('/requests', [RequestController::class, 'store'])->middleware(['role:school_manager,secretary', 'idempotency']);
        Route::get('/requests/{id}', [RequestController::class, 'show'])->whereNumber('id');
        Route::post('/requests/{id}/triage', [RequestActionsController::class, 'triage'])->middleware(['role:chairman,secretary', 'idempotency'])->whereNumber('id');
        Route::post('/requests/{id}/assignment', [RequestActionsController::class, 'assign'])->middleware(['role:chairman,secretary', 'idempotency'])->whereNumber('id');
        Route::post('/requests/{id}/start', [RequestActionsController::class, 'start'])->middleware('idempotency')->whereNumber('id');
        Route::post('/requests/{id}/hold', [RequestActionsController::class, 'hold'])->middleware('idempotency')->whereNumber('id');
        Route::post('/requests/{id}/resume', [RequestActionsController::class, 'resume'])->middleware('idempotency')->whereNumber('id');
        Route::post('/requests/{id}/close', [RequestActionsController::class, 'close'])->middleware('idempotency')->whereNumber('id');
        Route::post('/requests/{id}/cancel', [RequestActionsController::class, 'cancel'])->middleware(['role:school_manager,secretary,chairman', 'idempotency'])->whereNumber('id');
        Route::post('/requests/{id}/reopen', [RequestActionsController::class, 'reopen'])->middleware(['role:school_manager,chairman', 'idempotency'])->whereNumber('id');
        Route::get('/requests/{id}/cycles', [RequestController::class, 'cycles'])->whereNumber('id');
        Route::get('/requests/{id}/assignments', [RequestController::class, 'assignments'])->whereNumber('id');
        Route::post('/requests/{id}/notes', [RequestController::class, 'storeNote'])->middleware('idempotency')->whereNumber('id');
        Route::get('/requests/{id}/notes', [RequestController::class, 'notes'])->whereNumber('id');
        Route::post('/requests/{id}/proposals', [RequestController::class, 'propose'])->middleware(['role:engineer,technician', 'idempotency'])->whereNumber('id');

        Route::middleware('role:chairman,secretary,engineer,technician')->group(function () {
            Route::get('/tasks', [TaskController::class, 'index']);
            Route::get('/tasks/{id}', [TaskController::class, 'show'])->whereNumber('id');
            Route::post('/tasks/{id}/start', [TaskController::class, 'start'])->middleware('idempotency')->whereNumber('id');
            Route::post('/tasks/{id}/hold', [TaskController::class, 'hold'])->middleware('idempotency')->whereNumber('id');
            Route::post('/tasks/{id}/resume', [TaskController::class, 'resume'])->middleware('idempotency')->whereNumber('id');
            Route::post('/tasks/{id}/complete', [TaskController::class, 'complete'])->middleware('idempotency')->whereNumber('id');
            Route::post('/tasks/{id}/proposals', [TaskController::class, 'propose'])->middleware(['role:engineer,technician', 'idempotency'])->whereNumber('id');
        });
        Route::middleware('role:chairman,secretary')->group(function () {
            Route::post('/tasks', [TaskController::class, 'store'])->middleware('idempotency');
            Route::post('/tasks/{id}/assignment', [TaskController::class, 'assign'])->middleware('idempotency')->whereNumber('id');
            Route::post('/tasks/{id}/cancel', [TaskController::class, 'cancel'])->middleware('idempotency')->whereNumber('id');
            Route::get('/proposals', [ProposalController::class, 'index']);
            Route::post('/proposals/{id}/accept', [ProposalController::class, 'accept'])->middleware('idempotency')->whereNumber('id');
            Route::post('/proposals/{id}/reject', [ProposalController::class, 'reject'])->middleware('idempotency')->whereNumber('id');
        });

        Route::get('/attachments', [AttachmentController::class, 'index']);
        Route::post('/attachments', [AttachmentController::class, 'store'])->middleware('idempotency');
        Route::get('/attachments/{id}/download', [AttachmentController::class, 'download'])->whereNumber('id');

        Route::put('/drafts', [DraftController::class, 'put']);
        Route::get('/drafts', [DraftController::class, 'index']);
        Route::delete('/drafts/{id}', [DraftController::class, 'destroy'])->whereNumber('id');

        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->whereNumber('id');

        // Everything below is chairman-only in M1 (Batch 3 §4).
        Route::middleware('role:chairman')->group(function () {

            // 2.2 Users & Sessions
            Route::post('/users', [UserController::class, 'store'])->middleware('idempotency');
            Route::get('/users/{user}', [UserController::class, 'show']);
            Route::patch('/users/{user}', [UserController::class, 'update']);
            Route::post('/users/{user}/disable', [UserController::class, 'disable']);
            Route::post('/users/{user}/enable', [UserController::class, 'enable']);
            Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword']);
            Route::post('/users/{user}/sessions/terminate', [UserController::class, 'terminateSessions']);
            Route::get('/users/{user}/permission-grants', [UserController::class, 'permissionGrantsIndex']);
            Route::post('/users/{user}/permission-grants', [PermissionGrantController::class, 'store'])->middleware('idempotency');
            Route::post('/permission-grants/{permissionGrant}/revoke', [PermissionGrantController::class, 'revoke']);
            Route::get('/users/{user}/site-scopes', [UserController::class, 'siteScopesIndex']);
            Route::put('/users/{user}/site-scopes', [UserController::class, 'siteScopesReplace']);

            // 2.3 Sites & Site Managers
            Route::post('/sites', [SiteController::class, 'store'])->middleware('idempotency');
            Route::patch('/sites/{site}', [SiteController::class, 'update']);
            Route::post('/sites/{site}/archive', [SiteController::class, 'archive']);
            Route::get('/sites/{site}/managers', [SiteManagerController::class, 'index']);
            Route::post('/sites/{site}/managers', [SiteManagerController::class, 'store'])->middleware('idempotency');
            Route::post('/site-managers/{siteManager}/end', [SiteManagerController::class, 'end']);

            // 2.4 Settings & Reference Data
            Route::get('/settings', [SettingController::class, 'index']);
            Route::patch('/settings/{key}', [SettingController::class, 'update']);

            Route::post('/reference/{resource}', [ReferenceDataController::class, 'store'])
                ->whereIn('resource', ['device-categories', 'request-types', 'task-types', 'intake-channels'])
                ->middleware('idempotency');
            Route::patch('/reference/{resource}/{id}', [ReferenceDataController::class, 'update'])
                ->whereIn('resource', ['device-categories', 'request-types', 'task-types', 'intake-channels']);

            // 2.5 Audit & Integrity — read-only, no write route exists (IN-12).
            Route::get('/audit-log', [AuditLogController::class, 'index']);
            Route::get('/audit/chain-checks', [AuditLogController::class, 'chainChecks']);
        });
    });
});
