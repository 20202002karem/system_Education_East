<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetIdentifierCorrection;
use App\Models\AssetLegacyNumber;
use App\Models\AssetStatusHistory;
use App\Models\AuditLog;
use App\Models\DeviceCategory;
use App\Models\PermissionGrant;
use App\Models\Session;
use App\Models\Site;
use App\Models\SiteManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** M2 Assets — API-AST-01..09. Uses real tokens + sessions rows (session.activity requires them). */
class AssetTest extends TestCase
{
    use RefreshDatabase;

    protected function as(User $user): static
    {
        $token = $user->createToken('api', ['*'], now()->addHour());
        Session::create([
            'id' => (string) $token->accessToken->id, 'user_id' => $user->id,
            'last_activity_at' => now('UTC'), 'expires_at' => now()->addHour(), 'ip' => '127.0.0.1',
        ]);

        $this->app['auth']->forgetGuards(); // the guard caches the previous request's user

        return $this->withToken($token->plainTextToken);
    }

    protected function idem(): array
    {
        return ['Idempotency-Key' => uniqid('k', true)];
    }

    protected function chairman(): User
    {
        return User::factory()->chairman()->create();
    }

    protected function category(): DeviceCategory
    {
        return DeviceCategory::create(['name' => 'حاسوب '.uniqid()]);
    }

    protected function asset(Site $site, array $over = []): Asset
    {
        static $n = 0;
        $n++;

        return Asset::create($over + [
            'inventory_no' => 'PC-'.str_pad((string) $n, 6, '0', STR_PAD_LEFT),
            'category_id' => $this->category()->id, 'current_site_id' => $site->id,
        ])->refresh();
    }

    protected function grant(User $u): void
    {
        PermissionGrant::create(['user_id' => $u->id, 'permission_key' => 'edit_assets', 'granted_by' => $this->chairman()->id, 'granted_at' => now()]);
    }

    public function test_create_normalizes_defaults_status_audits_and_stores_legacy_numbers(): void
    {
        $site = Site::factory()->create();
        $cat = $this->category();
        $chair = $this->chairman();

        $r = $this->as($chair)->postJson('/api/v1/assets', [
            'inventory_no' => '  pc  000501 ', 'category_id' => $cat->id, 'current_site_id' => $site->id,
            'legacy_numbers' => ['OLD-1', 'OLD-2'],
        ], $this->idem());

        $r->assertCreated()->assertJsonPath('data.inventory_no', 'PC 000501')
            ->assertJsonPath('data.status', 'working')->assertJsonPath('data.version', 1)
            ->assertJsonPath('data.serial_no', null);
        $this->assertSame(2, AssetLegacyNumber::count());
        $this->assertTrue(AuditLog::where('action', 'asset.created')->where('entity_id', (string) $r->json('data.id'))->exists());
    }

    public function test_create_requires_idempotency_key_and_replays_same_result(): void
    {
        $site = Site::factory()->create();
        $cat = $this->category();
        $body = ['inventory_no' => 'PC-1', 'category_id' => $cat->id, 'current_site_id' => $site->id];
        $this->as($this->chairman())->postJson('/api/v1/assets', $body)->assertStatus(400)
            ->assertJsonPath('error.code', 'idempotency_key_required');

        $h = $this->idem();
        $chair = $this->chairman();
        $a = $this->as($chair)->postJson('/api/v1/assets', $body, $h)->assertCreated();
        $b = $this->as($chair)->postJson('/api/v1/assets', $body, $h)->assertCreated();
        $this->assertSame($a->json('data.id'), $b->json('data.id'));
        $this->assertSame(1, Asset::count());
    }

    public function test_create_rejects_duplicates_placeholder_serial_and_status_field(): void
    {
        $site = Site::factory()->create();
        $cat = $this->category();
        $chair = $this->chairman();
        $base = ['category_id' => $cat->id, 'current_site_id' => $site->id];
        $this->as($chair)->postJson('/api/v1/assets', $base + ['inventory_no' => 'PC-1', 'serial_no' => 'SN1'], $this->idem())->assertCreated();

        $this->as($chair)->postJson('/api/v1/assets', $base + ['inventory_no' => ' pc-1'], $this->idem())
            ->assertStatus(409)->assertJsonPath('error.code', 'duplicate_inventory_no');
        $this->as($chair)->postJson('/api/v1/assets', $base + ['inventory_no' => 'PC-2', 'serial_no' => 'sn1'], $this->idem())
            ->assertStatus(409)->assertJsonPath('error.code', 'duplicate_serial_no');
        $this->as($chair)->postJson('/api/v1/assets', $base + ['inventory_no' => 'PC-3', 'serial_no' => 'n/a'], $this->idem())
            ->assertStatus(422);
        $this->as($chair)->postJson('/api/v1/assets', $base + ['inventory_no' => 'PC-4', 'status' => 'broken'], $this->idem())
            ->assertStatus(422);
        $this->as($chair)->postJson('/api/v1/assets', $base + ['inventory_no' => 'PC-5', 'legacy_numbers' => ['A', 'a']], $this->idem())
            ->assertStatus(422);
        $this->as($chair)->postJson('/api/v1/assets', ['inventory_no' => 'PC-6'], $this->idem())->assertStatus(422);
        $this->as($chair)->postJson('/api/v1/assets', ['inventory_no' => 'PC-7', 'category_id' => 99999, 'current_site_id' => $site->id], $this->idem())->assertStatus(404);
    }

    public function test_create_authorization_matrix(): void
    {
        $site = Site::factory()->create();
        $other = Site::factory()->create();
        $body = fn ($sid, $n) => ['inventory_no' => $n, 'category_id' => $this->category()->id, 'current_site_id' => $sid];

        $sm = User::factory()->schoolManager()->create();
        SiteManager::create(['site_id' => $site->id, 'user_id' => $sm->id, 'from' => now()->toDateString()]);
        $this->grant($sm); // school manager can never write, even with a grant
        $this->as($sm)->postJson('/api/v1/assets', $body($site->id, 'A1'), $this->idem())->assertForbidden();

        $sec = User::factory()->secretary()->create();
        $this->as($sec)->postJson('/api/v1/assets', $body($site->id, 'A2'), $this->idem())->assertForbidden();

        $eng = User::factory()->engineer()->create();
        $eng->siteScopes()->create(['site_id' => $site->id]);
        $this->grant($eng);
        $this->as($eng)->postJson('/api/v1/assets', $body($site->id, 'A3'), $this->idem())->assertCreated();
        $this->as($eng)->postJson('/api/v1/assets', $body($other->id, 'A4'), $this->idem())->assertForbidden();

        $this->app['auth']->forgetGuards();
        $this->flushHeaders()->postJson('/api/v1/assets', [], $this->idem())->assertUnauthorized();
    }

    public function test_list_is_scoped_filtered_sorted_and_paginated(): void
    {
        $s1 = Site::factory()->create();
        $s2 = Site::factory()->create();
        $a = $this->asset($s1, ['inventory_no' => 'B-2', 'serial_no' => 'SER-X']);
        $b = $this->asset($s1, ['inventory_no' => 'A-1', 'status' => 'broken']);
        $c = $this->asset($s2, ['inventory_no' => 'C-3']);

        $chair = $this->chairman();
        $this->as($chair)->getJson('/api/v1/assets')->assertOk()->assertJsonPath('meta.total', 3)
            ->assertJsonPath('data.0.inventory_no', 'A-1');
        $this->as($chair)->getJson('/api/v1/assets?sort=-inventory_no')->assertJsonPath('data.0.inventory_no', 'C-3');
        $this->as($chair)->getJson('/api/v1/assets?status=broken')->assertJsonPath('meta.total', 1);
        $this->as($chair)->getJson('/api/v1/assets?q=ser-x')->assertJsonPath('meta.total', 1);
        $this->as($chair)->getJson('/api/v1/assets?site_id='.$s2->id)->assertJsonPath('meta.total', 1);
        $this->as($chair)->getJson('/api/v1/assets?category_id='.$a->category_id)->assertJsonPath('meta.total', 1);
        $this->as($chair)->getJson('/api/v1/assets?per_page=2')->assertJsonPath('meta.per_page', 2)->assertJsonCount(2, 'data');
        $this->as($chair)->getJson('/api/v1/assets?per_page=500')->assertJsonPath('meta.per_page', 100);
        $this->as($chair)->getJson('/api/v1/assets?status=in_transfer')->assertStatus(422);
        $this->as($chair)->getJson('/api/v1/assets?sort=holder_text')->assertStatus(422);

        $sm = User::factory()->schoolManager()->create();
        SiteManager::create(['site_id' => $s1->id, 'user_id' => $sm->id, 'from' => now()->toDateString()]);
        $this->as($sm)->getJson('/api/v1/assets')->assertJsonPath('meta.total', 2);
        // site_id filter is AND with the actor scope, never a bypass
        $this->as($sm)->getJson('/api/v1/assets?site_id='.$s2->id)->assertJsonPath('meta.total', 0);

        $eng = User::factory()->engineer()->create();
        $eng->siteScopes()->create(['site_id' => $s2->id]);
        $this->as($eng)->getJson('/api/v1/assets')->assertJsonPath('meta.total', 1);

        $tech = User::factory()->create();
        $this->as($tech)->getJson('/api/v1/assets')->assertOk()->assertJsonPath('meta.total', 0);
        $this->as(User::factory()->secretary()->create())->getJson('/api/v1/assets')->assertJsonPath('meta.total', 3);
    }

    public function test_show_and_subresources_out_of_scope_return_404(): void
    {
        $s1 = Site::factory()->create();
        $s2 = Site::factory()->create();
        $asset = $this->asset($s2);
        $sm = User::factory()->schoolManager()->create();
        SiteManager::create(['site_id' => $s1->id, 'user_id' => $sm->id, 'from' => now()->toDateString()]);

        foreach (['', '/status-history', '/identifier-corrections', '/legacy-numbers'] as $suffix) {
            $this->as($sm)->getJson("/api/v1/assets/{$asset->id}{$suffix}")->assertNotFound();
            $this->as($this->chairman())->getJson("/api/v1/assets/{$asset->id}{$suffix}")->assertOk();
        }
        $this->as($this->chairman())->getJson('/api/v1/assets/999999')->assertNotFound();
        $this->as($sm)->patchJson("/api/v1/assets/{$asset->id}", ['version' => 1])->assertNotFound();
    }

    public function test_patch_updates_metadata_with_optimistic_lock_and_audit(): void
    {
        $site = Site::factory()->create();
        $asset = $this->asset($site);
        $cat2 = $this->category();
        $chair = $this->chairman();

        $this->as($chair)->patchJson("/api/v1/assets/{$asset->id}", ['version' => 1, 'category_id' => $cat2->id, 'holder_text' => 'مختبر'])
            ->assertOk()->assertJsonPath('data.version', 2)->assertJsonPath('data.holder_text', 'مختبر');
        $this->assertTrue(AuditLog::where('action', 'asset.updated')->exists());

        $this->as($chair)->patchJson("/api/v1/assets/{$asset->id}", ['version' => 1, 'holder_text' => 'x'])
            ->assertStatus(409)->assertJsonPath('error.code', 'version_conflict');
        $this->as($chair)->patchJson("/api/v1/assets/{$asset->id}", ['holder_text' => 'x'])->assertStatus(422);
        foreach (['inventory_no' => 'Z', 'serial_no' => 'Z', 'status' => 'broken', 'current_site_id' => 1] as $f => $v) {
            $this->as($chair)->patchJson("/api/v1/assets/{$asset->id}", ['version' => 2, $f => $v])->assertStatus(422);
        }
        $this->as($chair)->patchJson("/api/v1/assets/{$asset->id}", ['version' => 2, 'category_id' => 99999])->assertNotFound();
        $this->assertSame(2, $asset->fresh()->version);

        $sec = User::factory()->secretary()->create();
        $this->as($sec)->patchJson("/api/v1/assets/{$asset->id}", ['version' => 2, 'holder_text' => 'x'])->assertForbidden();
        $this->grant($sec);
        $this->as($sec)->patchJson("/api/v1/assets/{$asset->id}", ['version' => 2, 'holder_text' => 'x'])->assertOk();
    }

    public function test_status_change_writes_history_audit_and_blocks_reserved_values(): void
    {
        $site = Site::factory()->create();
        $asset = $this->asset($site);
        $chair = $this->chairman();

        $r = $this->as($chair)->postJson("/api/v1/assets/{$asset->id}/status-changes",
            ['to_status' => 'under_maintenance', 'version' => 1], $this->idem())->assertCreated();
        $r->assertJsonPath('data.asset.status', 'under_maintenance')->assertJsonPath('data.asset.version', 2)
            ->assertJsonPath('data.status_history_entry.from_status', 'working')
            ->assertJsonPath('data.status_history_entry.reason', null);
        $this->assertSame(1, AssetStatusHistory::count());
        $this->assertTrue(AuditLog::where('action', 'asset.status_changed')->exists());

        foreach (['in_transfer', 'decommissioned', 'nonsense'] as $bad) {
            $this->as($chair)->postJson("/api/v1/assets/{$asset->id}/status-changes", ['to_status' => $bad, 'version' => 2], $this->idem())->assertStatus(422);
        }
        $this->as($chair)->postJson("/api/v1/assets/{$asset->id}/status-changes", ['to_status' => 'broken', 'version' => 1], $this->idem())->assertStatus(409);
        $this->as($chair)->postJson("/api/v1/assets/{$asset->id}/status-changes", ['to_status' => 'broken', 'reason' => 'سقط', 'version' => 2])->assertStatus(400);

        $this->as($chair)->getJson("/api/v1/assets/{$asset->id}/status-history")->assertOk()->assertJsonPath('meta.total', 1);

        $locked = $this->asset($site, ['status' => 'in_transfer']);
        $this->as($chair)->postJson("/api/v1/assets/{$locked->id}/status-changes", ['to_status' => 'working', 'version' => 1], $this->idem())->assertStatus(422);

        $eng = User::factory()->engineer()->create();
        $eng->siteScopes()->create(['site_id' => $site->id]);
        $this->as($eng)->postJson("/api/v1/assets/{$asset->id}/status-changes", ['to_status' => 'broken', 'version' => 2], $this->idem())->assertForbidden();
        $this->grant($eng);
        $this->as($eng)->postJson("/api/v1/assets/{$asset->id}/status-changes", ['to_status' => 'broken', 'version' => 2], $this->idem())->assertCreated();
    }

    public function test_identifier_correction_is_chairman_only_with_reason_and_history(): void
    {
        $site = Site::factory()->create();
        $asset = $this->asset($site, ['inventory_no' => 'PC-000501']);
        $other = $this->asset($site, ['inventory_no' => 'PC-9', 'serial_no' => 'SER-9']);
        $chair = $this->chairman();
        $url = "/api/v1/assets/{$asset->id}/identifier-corrections";

        $sec = User::factory()->secretary()->create();
        $this->grant($sec);
        $this->as($sec)->postJson($url, ['field_name' => 'inventory_no', 'new_value' => 'X', 'reason' => 'r'], $this->idem())->assertForbidden();

        $this->as($chair)->postJson($url, ['field_name' => 'inventory_no', 'new_value' => 'X', 'reason' => '  '], $this->idem())->assertStatus(422);
        $this->as($chair)->postJson($url, ['field_name' => 'inventory_no', 'new_value' => 'pc-9', 'reason' => 'r'], $this->idem())->assertStatus(409);
        $this->as($chair)->postJson($url, ['field_name' => 'serial_no', 'new_value' => 'TBD', 'reason' => 'r'], $this->idem())->assertStatus(422);
        $this->as($chair)->postJson($url, ['field_name' => 'colour', 'new_value' => 'x', 'reason' => 'r'], $this->idem())->assertStatus(422);

        $this->as($chair)->postJson($url, ['field_name' => 'inventory_no', 'new_value' => 'pc-000777', 'reason' => 'خطأ إدخال'], $this->idem())
            ->assertCreated()->assertJsonPath('data.asset.inventory_no', 'PC-000777')
            ->assertJsonPath('data.correction.old_value', 'PC-000501')->assertJsonPath('data.correction.corrected_by', $chair->id);
        $this->assertSame(1, AssetIdentifierCorrection::count());
        $this->assertSame(2, $asset->fresh()->version);
        $log = AuditLog::where('action', 'asset.identifier_corrected')->first();
        $this->assertNotNull($log);

        $this->as($sec)->getJson($url)->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.new_value', 'PC-000777');
    }

    public function test_legacy_numbers_listing_and_append_only_models(): void
    {
        $site = Site::factory()->create();
        $chair = $this->chairman();
        $r = $this->as($chair)->postJson('/api/v1/assets', [
            'inventory_no' => 'PC-1', 'category_id' => $this->category()->id, 'current_site_id' => $site->id, 'legacy_numbers' => ['OLD-1'],
        ], $this->idem())->assertCreated();

        $this->as($chair)->getJson('/api/v1/assets/'.$r->json('data.id').'/legacy-numbers')
            ->assertOk()->assertJsonPath('data.0.legacy_number', 'OLD-1')->assertJsonPath('data.0.added_by', $chair->id);

        $this->expectException(\LogicException::class);
        AssetLegacyNumber::first()->update(['source' => 'x']);
    }

    public function test_no_delete_route_exists(): void
    {
        $asset = $this->asset(Site::factory()->create());
        $this->as($this->chairman())->deleteJson("/api/v1/assets/{$asset->id}")->assertStatus(405);
    }
}
