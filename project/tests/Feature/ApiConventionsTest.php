<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiConventionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/v1/auth/me')->assertStatus(401)
            ->assertJsonStructure(['error' => ['code', 'message']]);
    }

    public function test_list_endpoint_returns_pagination_meta(): void
    {
        $chairman = User::factory()->chairman()->create();
        $this->signIn($chairman);
        User::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/users?page=1&per_page=20');

        $response->assertOk()->assertJsonStructure(['data', 'meta' => ['page', 'per_page', 'total']]);
    }

    public function test_validation_error_uses_422_and_fields_envelope(): void
    {
        $chairman = User::factory()->chairman()->create();
        $this->signIn($chairman);

        $response = $this->withHeader('Idempotency-Key', 'k1')->postJson('/api/v1/users', [
            'name' => '',
            'login_identifier' => 'not-an-email',
            'role' => 'ministry_admin', // not one of the 5 valid roles
            'view_scope' => 'all',
        ]);

        $response->assertStatus(422)->assertJsonStructure(['error' => ['code', 'message', 'fields']]);
    }
}
