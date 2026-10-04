<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\MfaVerifyRequest;
use App\Models\LoginAttempt;
use App\Models\Session as SessionModel;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TotpService;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Batch 3 §2.1 / §3.1. POST /auth/login returns either a full token (non-chairman,
 * or chairman with mfa not yet enabled — see note below) or an mfa_challenge_token
 * (chairman). POST /auth/mfa/verify exchanges the challenge for a full token.
 *
 * Uniform failure message (MD-01): invalid_credentials never distinguishes
 * "account doesn't exist" from "wrong password" from "account disabled" —
 * all three return the same 401 body, except the 423 locked-account case
 * which is intentionally distinguishable (Batch 3 §3.1 shows both explicitly).
 */
class AuthController extends Controller
{
    public function __construct(
        protected AuditLogger $audit,
        protected TotpService $totp,
    ) {}

    public function login(LoginRequest $request)
    {
        $identifier = $request->string('login_identifier')->toString();
        $password = $request->string('password')->toString();
        $ip = $request->ip();

        $user = User::where('email', $identifier)->first();

        // Lockout check happens before password verification so a locked
        // account never leaks whether the password itself was correct.
        if ($user && $user->locked_until && now('UTC')->lessThan($user->locked_until)) {
            return ApiResponse::error('account_locked', 'الحساب مقفل مؤقتاً، حاول لاحقاً', 423);
        }

        $valid = $user && $user->isActive() && Hash::check($password, $user->password_hash);

        $this->recordAttempt($user, $identifier, $valid, $ip);

        if (! $valid) {
            if ($user) {
                $this->registerFailure($user);
            }

            return ApiResponse::error('invalid_credentials', 'بيانات الدخول غير صحيحة', 401);
        }

        // Successful password check resets the failure counter.
        $user->forceFill(['failed_login_count' => 0, 'locked_until' => null])->save();

        return ApiResponse::ok($this->issueToken($user, $ip));
    }

    public function mfaVerify(MfaVerifyRequest $request)
    {
        $challengeToken = $request->string('mfa_challenge_token')->toString();
        $payload = Cache::get("mfa_challenge:{$challengeToken}");

        if (! $payload) {
            return ApiResponse::error('invalid_challenge', 'رمز التحدي غير صالح أو منتهي', 401);
        }

        $user = User::find($payload['user_id']);

        if (! $user || ! $user->isActive() || ! $user->mfa_enabled || ! $user->mfa_secret) {
            return ApiResponse::error('invalid_challenge', 'رمز التحدي غير صالح أو منتهي', 401);
        }

        if (! $this->totp->verify($user->mfa_secret, $request->string('code')->toString())) {
            return ApiResponse::error('invalid_mfa_code', 'رمز التحقق غير صحيح', 401);
        }

        Cache::forget("mfa_challenge:{$challengeToken}");

        return ApiResponse::ok($this->issueToken($user, $request->ip()));
    }

    public function logout()
    {
        $user = request()->user();
        $token = $user->currentAccessToken();

        SessionModel::where('id', (string) $token->id)->delete();
        $token->delete();

        return ApiResponse::noContent();
    }

    public function me()
    {
        $user = request()->user();

        return ApiResponse::ok([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'view_scope' => $user->view_scope,
            'status' => $user->status,
            'mfa_enabled' => $user->mfa_enabled,
            // mfa_secret is never included — enforced by $hidden on the model too.
            'site_scope_ids' => $user->view_scope === 'sites' ? $user->siteScopes()->pluck('site_id') : [],
        ]);
    }

    protected function issueToken(User $user, ?string $ip): array
    {
        $ttlMinutes = (int) config('m1.auth.token_ttl_minutes');
        $expiresAt = now('UTC')->addMinutes($ttlMinutes);

        $token = $user->createToken('api', ['*'], $expiresAt);

        SessionModel::create([
            'id' => (string) $token->accessToken->id,
            'user_id' => $user->id,
            'last_activity_at' => now('UTC'),
            'expires_at' => $expiresAt,
            'ip' => $ip,
        ]);

        return [
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_in' => $ttlMinutes * 60,
        ];
    }

    protected function recordAttempt(?User $user, string $identifier, bool $success, ?string $ip): void
    {
        LoginAttempt::create([
            'user_id' => $user?->id,
            'attempted_identifier' => $identifier,
            'success' => $success,
            'ip' => $ip,
            'attempted_at' => now('UTC'),
        ]);
    }

    protected function registerFailure(User $user): void
    {
        $max = (int) config('m1.auth.max_failed_attempts');
        $lockoutMinutes = (int) config('m1.auth.lockout_minutes');

        $count = $user->failed_login_count + 1;
        $update = ['failed_login_count' => $count];

        if ($count >= $max) {
            $update['locked_until'] = now('UTC')->addMinutes($lockoutMinutes);
            $update['failed_login_count'] = 0;

            // account.locked is security-relevant enough to be audited, even
            // though ordinary failed attempts are intentionally NOT (Batch 3 §8:
            // "دخول فاشل ... لا يُسجَّل في audit_log بل في login_attempts فقط").
            $this->audit->record(
                actorId: $user->id,
                action: 'account.locked',
                entityType: 'user',
                entityId: $user->id,
                reason: 'lockout after '.$max.' failed attempts',
            );
        }

        $user->forceFill($update)->save();
    }
}
