<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuditTest extends TestCase
{
    use RefreshDatabase;

    protected function chairman(): User
    {
        $chairman = User::factory()->chairman()->create();
        $this->signIn($chairman);

        return $chairman;
    }

    public function test_user_creation_is_audited(): void
    {
        $this->chairman();

        $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson('/api/v1/users', [
            'name' => 'موظف جديد',
            'login_identifier' => 'new.staff@moehe.example',
            'role' => 'technician',
            'view_scope' => 'assigned_only',
        ]);

        $this->assertDatabaseHas('audit_log', ['action' => 'user.created', 'entity_type' => 'user']);
    }

    public function test_permission_grant_and_revoke_are_audited(): void
    {
        $this->chairman();
        $user = User::factory()->create();

        $grant = $this->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson("/api/v1/users/{$user->id}/permission-grants", ['permission_key' => 'edit_assets']);
        $grantId = $grant->json('data.id');

        $this->postJson("/api/v1/permission-grants/{$grantId}/revoke");

        $this->assertDatabaseHas('audit_log', ['action' => 'permission.granted']);
        $this->assertDatabaseHas('audit_log', ['action' => 'permission.revoked']);
    }

    public function test_no_route_exists_to_modify_or_delete_audit_records(): void
    {
        $this->chairman();
        app(AuditLogger::class)->record(1, 'user.created', 'user', 1);
        $log = AuditLog::first();

        $this->patchJson("/api/v1/audit-log/{$log->seq}", ['reason' => 'tamper'])->assertStatus(404);
        $this->putJson("/api/v1/audit-log/{$log->seq}", ['reason' => 'tamper'])->assertStatus(404);
        $this->deleteJson("/api/v1/audit-log/{$log->seq}")->assertStatus(404);
    }

    public function test_audit_log_hash_chain_links_sequentially(): void
    {
        $logger = app(AuditLogger::class);
        $logger->record(1, 'user.created', 'user', 1);
        $logger->record(1, 'user.updated', 'user', 1);

        $rows = AuditLog::orderBy('seq')->get();
        $this->assertSame($rows[1]->prev_hash, $rows[0]->hash);

        $result = $logger->verifyChain();
        $this->assertSame('ok', $result['result']);
    }

    public function test_broken_chain_is_detected(): void
    {
        $logger = app(AuditLogger::class);
        $logger->record(1, 'user.created', 'user', 1);
        $logger->record(1, 'user.updated', 'user', 1);

        // Tamper directly at the DB layer (simulating someone bypassing the app).
        AuditLog::first()->update(['action' => 'user.tampered']);
        AuditLog::query()->update(['action' => 'user.tampered']); // ensure a row's content changed post-hash

        $result = $logger->verifyChain();
        $this->assertSame('broken', $result['result']);
    }

    public function test_secrets_are_never_written_to_audit_log(): void
    {
        $this->chairman();

        $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson('/api/v1/users', [
            'name' => 'موظف',
            'login_identifier' => 'secret.check@moehe.example',
            'role' => 'technician',
            'view_scope' => 'assigned_only',
            'password' => 'SuperSecretPass1',
        ]);

        $rows = AuditLog::all();
        foreach ($rows as $row) {
            $haystack = json_encode([$row->before, $row->after, $row->reason]);
            $this->assertStringNotContainsString('SuperSecretPass1', $haystack);
        }
    }
}
