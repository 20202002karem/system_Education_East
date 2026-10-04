<?php

namespace Tests\Feature;

use App\Models\PermissionGrant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function chairman(): User
    {
        $chairman = User::factory()->chairman()->create();
        Sanctum::actingAs($chairman, ['*']);

        return $chairman;
    }

    public function test_create_user_requires_idempotency_key(): void
    {
        $this->chairman();

        $response = $this->postJson('/api/v1/users', [
            'name' => 'أحمد س.',
            'login_identifier' => 'ahmad@moehe.example',
            'role' => 'engineer',
            'view_scope' => 'all',
        ]);

        $response->assertStatus(400)->assertJson(['error' => ['code' => 'idempotency_key_required']]);
    }

    public function test_create_user_with_sites_scope_requires_site_scope_ids(): void
    {
        $this->chairman();

        $response = $this->withHeader('Idempotency-Key', Str::uuid())->postJson('/api/v1/users', [
            'name' => 'أحمد س.',
            'login_identifier' => 'ahmad2@moehe.example',
            'role' => 'engineer',
            'view_scope' => 'sites',
        ]);

        $response->assertStatus(422)->assertJsonPath('error.code', 'validation_failed');
    }

    public function test_create_user_success(): void
    {
        $this->chairman();

        $response = $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson('/api/v1/users', [
            'name' => 'خالد م.',
            'login_identifier' => 'khaled@moehe.example',
            'role' => 'technician',
            'view_scope' => 'assigned_only',
        ]);

        $response->assertStatus(201)->assertJsonPath('data.email', 'khaled@moehe.example');
        $this->assertDatabaseHas('users', ['email' => 'khaled@moehe.example', 'status' => 'active']);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        $this->chairman();
        $existing = User::factory()->create(['email' => 'dup@moehe.example']);

        $response = $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson('/api/v1/users', [
            'name' => 'نسخة',
            'login_identifier' => 'dup@moehe.example',
            'role' => 'technician',
            'view_scope' => 'assigned_only',
        ]);

        $response->assertStatus(422);
    }

    public function test_disable_then_enable_user(): void
    {
        $this->chairman();
        $user = User::factory()->create();

        $this->postJson("/api/v1/users/{$user->id}/disable")->assertOk();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'disabled']);

        $this->postJson("/api/v1/users/{$user->id}/enable")->assertOk();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'active']);
    }

    public function test_disabled_user_cannot_log_in(): void
    {
        $this->chairman();
        $user = User::factory()->create(['status' => 'disabled']);

        $response = $this->postJson('/api/v1/auth/login', [
            'login_identifier' => $user->email,
            'password' => 'Password-123',
        ]);

        $response->assertStatus(401)->assertJson(['error' => ['code' => 'invalid_credentials']]);
    }

    public function test_no_delete_route_exists_for_users(): void
    {
        $this->chairman();
        $user = User::factory()->create();

        $this->deleteJson("/api/v1/users/{$user->id}")->assertStatus(404);
    }

    public function test_reset_password(): void
    {
        $this->chairman();
        $user = User::factory()->create();

        $this->postJson("/api/v1/users/{$user->id}/reset-password", ['password' => 'NewPassword-999'])
            ->assertStatus(204);
    }

    public function test_grant_and_revoke_permission(): void
    {
        $chairman = $this->chairman();
        $user = User::factory()->create();

        $grant = $this->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson("/api/v1/users/{$user->id}/permission-grants", [
                'permission_key' => 'initiate_transfer',
                'reason' => 'تغطية غياب',
            ]);
        $grant->assertStatus(201)->assertJsonPath('data.permission_key', 'initiate_transfer');

        $grantId = $grant->json('data.id');
        $this->assertTrue($user->fresh()->hasActivePermission('initiate_transfer'));

        $this->postJson("/api/v1/permission-grants/{$grantId}/revoke")->assertOk();
        $this->assertFalse($user->fresh()->hasActivePermission('initiate_transfer'));
    }

    public function test_reserved_permission_key_is_rejected(): void
    {
        $this->chairman();
        $user = User::factory()->create();

        $response = $this->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson("/api/v1/users/{$user->id}/permission-grants", [
                'permission_key' => 'create_request', // D-22b reserved & disabled
            ]);

        $response->assertStatus(422);
    }
}
