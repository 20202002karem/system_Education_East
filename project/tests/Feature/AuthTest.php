<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TotpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_login_for_non_chairman_returns_full_token(): void
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('Password-123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login_identifier' => $user->email,
            'password' => 'Password-123',
        ]);

        $response->assertOk()->assertJsonStructure(['data' => ['access_token', 'token_type', 'expires_in']]);
    }

    public function test_invalid_credentials_returns_uniform_message(): void
    {
        $user = User::factory()->create(['password_hash' => Hash::make('Password-123')]);

        $wrongPassword = $this->postJson('/api/v1/auth/login', [
            'login_identifier' => $user->email,
            'password' => 'wrong-pass',
        ]);
        $nonExistent = $this->postJson('/api/v1/auth/login', [
            'login_identifier' => 'nobody@example.com',
            'password' => 'whatever',
        ]);

        $wrongPassword->assertStatus(401)->assertJson(['error' => ['code' => 'invalid_credentials']]);
        $nonExistent->assertStatus(401)->assertJson(['error' => ['code' => 'invalid_credentials']]);
        $this->assertSame(
            $wrongPassword->json('error.message'),
            $nonExistent->json('error.message'),
            'Failure message must not reveal whether the account exists.'
        );
    }

    public function test_account_locks_after_max_failed_attempts(): void
    {
        $user = User::factory()->create(['password_hash' => Hash::make('Password-123')]);
        $max = config('m1.auth.max_failed_attempts');

        for ($i = 0; $i < $max; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'login_identifier' => $user->email,
                'password' => 'wrong',
            ]);
        }

        $locked = $this->postJson('/api/v1/auth/login', [
            'login_identifier' => $user->email,
            'password' => 'Password-123', // even the correct password is now rejected
        ]);

        $locked->assertStatus(423)->assertJson(['error' => ['code' => 'account_locked']]);
    }

    public function test_chairman_login_requires_2fa_challenge(): void
    {
        $totp = app(TotpService::class);
        $secret = $totp->generateSecret();

        $chairman = User::factory()->chairman()->create([
            'password_hash' => Hash::make('Password-123'),
            'mfa_enabled' => true,
            'mfa_secret' => $secret,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login_identifier' => $chairman->email,
            'password' => 'Password-123',
        ]);

        $response->assertOk()->assertJson(['data' => ['mfa_required' => true]]);
        $response->assertJsonStructure(['data' => ['mfa_challenge_token']]);
    }

    public function test_valid_totp_completes_login(): void
    {
        $totp = app(TotpService::class);
        $secret = $totp->generateSecret();

        $chairman = User::factory()->chairman()->create([
            'password_hash' => Hash::make('Password-123'),
            'mfa_enabled' => true,
            'mfa_secret' => $secret,
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'login_identifier' => $chairman->email,
            'password' => 'Password-123',
        ]);
        $challengeToken = $login->json('data.mfa_challenge_token');

        $validCode = (new \PragmaRX\Google2FA\Google2FA())->getCurrentOtp($secret);

        $verify = $this->postJson('/api/v1/auth/mfa/verify', [
            'mfa_challenge_token' => $challengeToken,
            'code' => $validCode,
        ]);

        $verify->assertOk()->assertJsonStructure(['data' => ['access_token']]);
    }

    public function test_invalid_totp_is_rejected(): void
    {
        $totp = app(TotpService::class);
        $secret = $totp->generateSecret();

        $chairman = User::factory()->chairman()->create([
            'password_hash' => Hash::make('Password-123'),
            'mfa_enabled' => true,
            'mfa_secret' => $secret,
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'login_identifier' => $chairman->email,
            'password' => 'Password-123',
        ]);
        $challengeToken = $login->json('data.mfa_challenge_token');

        $verify = $this->postJson('/api/v1/auth/mfa/verify', [
            'mfa_challenge_token' => $challengeToken,
            'code' => '000000',
        ]);

        $verify->assertStatus(401)->assertJson(['error' => ['code' => 'invalid_mfa_code']]);
    }

    public function test_logout_revokes_token(): void
    {
        $user = User::factory()->create(['password_hash' => Hash::make('Password-123')]);
        $login = $this->postJson('/api/v1/auth/login', [
            'login_identifier' => $user->email,
            'password' => 'Password-123',
        ]);
        $token = $login->json('data.access_token');

        $me = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/auth/me');
        $me->assertOk();

        $logout = $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/v1/auth/logout');
        $logout->assertStatus(204);

        $meAfter = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/auth/me');
        $meAfter->assertStatus(401);
    }

    public function test_me_never_exposes_mfa_secret(): void
    {
        $totp = app(TotpService::class);
        $secret = $totp->generateSecret();
        $chairman = User::factory()->chairman()->create([
            'password_hash' => Hash::make('Password-123'),
            'mfa_enabled' => true,
            'mfa_secret' => $secret,
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'login_identifier' => $chairman->email,
            'password' => 'Password-123',
        ]);
        $validCode = (new \PragmaRX\Google2FA\Google2FA())->getCurrentOtp($secret);
        $verify = $this->postJson('/api/v1/auth/mfa/verify', [
            'mfa_challenge_token' => $login->json('data.mfa_challenge_token'),
            'code' => $validCode,
        ]);
        $token = $verify->json('data.access_token');

        $me = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/auth/me');
        $me->assertOk()->assertJsonMissingPath('data.mfa_secret');
    }
}
