<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SettingsReferenceTest extends TestCase
{
    use RefreshDatabase;

    protected function chairman(): User
    {
        $chairman = User::factory()->chairman()->create();
        Sanctum::actingAs($chairman, ['*']);

        return $chairman;
    }

    public function test_read_settings(): void
    {
        $this->chairman();
        Setting::create(['key' => 'reopen_window_days', 'value' => '7']);

        $this->getJson('/api/v1/settings')->assertOk();
    }

    public function test_update_setting_writes_history(): void
    {
        $this->chairman();
        Setting::create(['key' => 'reopen_window_days', 'value' => '7']);

        $response = $this->patchJson('/api/v1/settings/reopen_window_days', ['value' => '10']);

        $response->assertOk()->assertJsonPath('data.value', '10');
        $this->assertDatabaseHas('settings_history', [
            'key' => 'reopen_window_days',
            'old_value' => '7',
            'new_value' => '10',
        ]);
    }

    public function test_reference_data_crud_allowed_by_design(): void
    {
        $this->chairman();

        $create = $this->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/reference/device-categories', ['name' => 'حاسوب مكتبي']);
        $create->assertStatus(201);

        $id = $create->json('data.id');

        $this->patchJson("/api/v1/reference/device-categories/{$id}", ['name' => 'حاسوب مكتبي محدث'])
            ->assertOk()
            ->assertJsonPath('data.name', 'حاسوب مكتبي محدث');

        $this->getJson('/api/v1/reference/device-categories')->assertOk();
    }

    public function test_task_types_have_administrative_flags(): void
    {
        $this->chairman();

        $create = $this->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/reference/task-types', [
                'name' => 'جرد ورقي',
                'is_administrative' => true,
                'secretary_assignable' => true,
            ]);

        $create->assertStatus(201)
            ->assertJsonPath('data.is_administrative', true)
            ->assertJsonPath('data.secretary_assignable', true);
    }
}
