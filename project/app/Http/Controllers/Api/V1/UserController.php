<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\SiteScopesRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Session as SessionModel;
use App\Models\User;
use App\Models\UserSiteScope;
use App\Services\AuditLogger;
use App\Services\IdempotencyService;
use App\Services\PasswordPolicy;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Batch 3 §2.2 — Users & Sessions. Every route here is chairman-only
 * (route middleware role:chairman) per the Authorization Matrix §4.
 */
class UserController extends Controller
{
    public function __construct(
        protected AuditLogger $audit,
        protected IdempotencyService $idempotency,
        protected PasswordPolicy $passwordPolicy,
    ) {}

    public function index(Request $request)
    {
        $perPage = min((int) $request->query('per_page', 20), 100);
        $query = User::query();
        $actor = $request->user();
        $minimal = false;
        if (! $actor->isChairman()) {
            // M3 BASELINE CHANGE (read-only): the secretary picks assignees; only active engineers/technicians, id/name/role.
            if ($actor->role !== 'secretary') {
                abort(403);
            }
            $query->where('status', 'active')->whereIn('role', ['engineer', 'technician']);
            $minimal = true;
        }

        if ($request->filled('role')) {
            $query->where('role', $request->query('role'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $paginator = $query->orderBy('id')->paginate($perPage, ['*'], 'page', (int) $request->query('page', 1));

        return ApiResponse::ok(
            $minimal ? collect($paginator->items())->map(fn ($u) => $u->only(['id', 'name', 'role']))->all() : $paginator->items(),
            200,
            ['page' => $paginator->currentPage(), 'per_page' => $perPage, 'total' => $paginator->total()]
        );
    }

    public function store(StoreUserRequest $request)
    {
        $idKey = $request->header('Idempotency-Key');
        $actor = $request->user();

        if ($cached = $this->idempotency->find($actor->id, $idKey)) {
            return response()->json($cached->response_snapshot, $cached->response_status);
        }

        $password = $request->input('password') ?? Str::password(12);
        $errors = $this->passwordPolicy->validate($password, $request->input('name'));
        if (! empty($errors)) {
            return ApiResponse::error('validation_failed', 'بيانات غير صالحة', 422, ['password' => $errors]);
        }

        $user = DB::transaction(function () use ($request, $password, $actor) {
    $user = new User([
        'name' => $request->input('name'),
        'email' => $request->input('login_identifier'),
        'role' => $request->input('role'),
        'view_scope' => $request->input('view_scope'),
        'specialization' => $request->input('specialization'),
    ]);

    $user->forceFill([
        'password_hash' => Hash::make($password),
    ]);

    $user->save();

    if ($request->input('view_scope') === 'sites') {
        foreach ($request->input('site_scope_ids', []) as $siteId) {
            UserSiteScope::create([
                'user_id' => $user->id,
                'site_id' => $siteId,
            ]);
        }
    }

    $this->audit->record(
        actorId: $actor->id,
        action: 'user.created',
        entityType: 'user',
        entityId: $user->id,
        after: [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'view_scope' => $user->view_scope,
        ],
    );

    return $user;
});

        $response = ApiResponse::created($this->present($user));
        $this->idempotency->remember($actor->id, $idKey, $response);

        return $response;
    }

    public function show(User $user)
    {
        return ApiResponse::ok($this->present($user));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $actor = $request->user();
        $before = $user->only(['name', 'role', 'view_scope', 'specialization']);

        DB::transaction(function () use ($request, $user) {
            $user->fill($request->only(['name', 'role', 'view_scope', 'specialization']));
            $user->save();

            if ($request->has('site_scope_ids')) {
                $user->siteScopes()->delete();
                foreach ($request->input('site_scope_ids', []) as $siteId) {
                    UserSiteScope::create(['user_id' => $user->id, 'site_id' => $siteId]);
                }
            }
        });

        $this->audit->record(
            actorId: $actor->id,
            action: 'user.updated',
            entityType: 'user',
            entityId: $user->id,
            before: $before,
            after: $user->only(['name', 'role', 'view_scope', 'specialization']),
        );

        return ApiResponse::ok($this->present($user->fresh()));
    }

    public function disable(Request $request, User $user)
    {
        $actor = $request->user();
        $user->update(['status' => 'disabled']);
        SessionModel::where('user_id', $user->id)->delete();
        $user->tokens()->delete();

        $this->audit->record($actor->id, 'user.disabled', 'user', $user->id);

        return ApiResponse::ok($this->present($user));
    }

    public function enable(Request $request, User $user)
    {
        $actor = $request->user();
        $user->update(['status' => 'active']);

        $this->audit->record($actor->id, 'user.enabled', 'user', $user->id);

        return ApiResponse::ok($this->present($user));
    }

    public function resetPassword(ResetPasswordRequest $request, User $user)
    {
        $actor = $request->user();
        $password = $request->input('password');

        $errors = $this->passwordPolicy->validate($password, $user->name);
        if (! empty($errors)) {
            return ApiResponse::error('validation_failed', 'بيانات غير صالحة', 422, ['password' => $errors]);
        }

        $user->forceFill(['password_hash' => Hash::make($password)])->save();
        // Force re-authentication everywhere after a password reset.
        SessionModel::where('user_id', $user->id)->delete();
        $user->tokens()->delete();

        $this->audit->record($actor->id, 'user.password_reset', 'user', $user->id);

        return ApiResponse::noContent();
    }

    public function terminateSessions(Request $request, User $user)
    {
        $actor = $request->user();
        SessionModel::where('user_id', $user->id)->delete();
        $user->tokens()->delete();

        $this->audit->record($actor->id, 'user.sessions_terminated', 'user', $user->id);

        return ApiResponse::noContent();
    }

    public function permissionGrantsIndex(User $user)
    {
        return ApiResponse::ok($user->permissionGrants()->orderByDesc('granted_at')->get());
    }

    public function siteScopesIndex(User $user)
    {
        return ApiResponse::ok($user->siteScopes()->with('site')->get());
    }

    public function siteScopesReplace(SiteScopesRequest $request, User $user)
    {
        $actor = $request->user();
        $before = $user->siteScopes()->pluck('site_id');

        DB::transaction(function () use ($request, $user) {
            $user->siteScopes()->delete();
            foreach ($request->input('site_ids', []) as $siteId) {
                UserSiteScope::create(['user_id' => $user->id, 'site_id' => $siteId]);
            }
        });

        $this->audit->record(
            $actor->id, 'user.site_scopes_replaced', 'user', $user->id,
            before: ['site_ids' => $before], after: ['site_ids' => $request->input('site_ids')]
        );

        return ApiResponse::ok($user->siteScopes()->with('site')->get());
    }

    protected function present(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'view_scope' => $user->view_scope,
            'status' => $user->status,
            'mfa_enabled' => $user->mfa_enabled,
            'specialization' => $user->specialization,
            'created_at' => $user->created_at->toIso8601String(),
        ];
    }
}
