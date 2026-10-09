<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Batch 3 §4 — Authorization Matrix. In M1 every admin group (Users,
 * Permission grants, Sites/Site-managers writes, Sites reads, Settings,
 * Audit log) is chairman-only; every other role is denied.
 */
class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAsRole(string $role): User
    {
        $user = User::factory()->create(['role' => $role]);
        $this->signIn($user);

        return $user;
    }

    public function test_chairman_can_list_users(): void
    {
        $this->actingAsRole('chairman');

        $this->getJson('/api/v1/users')->assertOk();
    }

    /** @dataProvider nonChairmanRoles */
    public function test_non_chairman_roles_cannot_list_users(string $role): void
    {
        $this->actingAsRole($role);

        // M3 BASELINE CHANGE: the secretary may read a minimal assignable-users list; everyone else stays 403.
        if ($role === 'secretary') {
            User::factory()->engineer()->create();
            $res = $this->getJson('/api/v1/users')->assertOk();
            $this->assertSame(['id', 'name', 'role'], array_keys($res->json('data.0')));
        } else {
            $this->getJson('/api/v1/users')->assertStatus(403);
        }
    }

    /** @dataProvider nonChairmanRoles */
    public function test_non_chairman_roles_read_only_scoped_minimal_sites(string $role): void
    {
        $this->actingAsRole($role);
        \App\Models\Site::factory()->create();

        $res = $this->getJson('/api/v1/sites')->assertOk();
        foreach ($res->json('data') as $row) {
            $this->assertSame(['id', 'type', 'code', 'name_ar', 'status'], array_keys($row));
        }
        $this->postJson('/api/v1/sites', [])->assertStatus(403); // writes stay chairman-only
    }

    /** @dataProvider nonChairmanRoles */
    public function test_non_chairman_roles_cannot_read_audit_log(string $role): void
    {
        $this->actingAsRole($role);

        $this->getJson('/api/v1/audit-log')->assertStatus(403);
    }

    /** @dataProvider nonChairmanRoles */
    public function test_non_chairman_roles_cannot_manage_settings(string $role): void
    {
        $this->actingAsRole($role);

        $this->getJson('/api/v1/settings')->assertStatus(403);
    }

    public static function nonChairmanRoles(): array
    {
        return [
            ['school_manager'],
            ['secretary'],
            ['engineer'],
            ['technician'],
        ];
    }
}
