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
        Sanctum::actingAs($user, ['*']);

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

        $this->getJson('/api/v1/users')->assertStatus(403);
    }

    /** @dataProvider nonChairmanRoles */
    public function test_non_chairman_roles_cannot_read_sites(string $role): void
    {
        $this->actingAsRole($role);

        $this->getJson('/api/v1/sites')->assertStatus(403);
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
