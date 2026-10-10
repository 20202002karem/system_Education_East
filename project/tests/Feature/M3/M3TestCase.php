<?php

namespace Tests\Feature\M3;

use App\Models\Asset;
use App\Models\DeviceCategory;
use App\Models\IntakeChannel;
use App\Models\RequestType;
use App\Models\ServiceRequest;
use App\Models\Site;
use App\Models\SiteManager;
use App\Models\TaskType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

abstract class M3TestCase extends TestCase
{
    use RefreshDatabase;

    protected User $chairman;
    protected User $secretary;
    protected User $engineer;
    protected User $technician;
    protected User $manager;
    protected Site $site;
    protected IntakeChannel $channel;
    protected RequestType $type;

    protected function setUp(): void
    {
        parent::setUp();
        $this->chairman = User::factory()->chairman()->create(['name' => 'رئيس']);
        $this->secretary = User::factory()->secretary()->create();
        $this->engineer = User::factory()->engineer()->create(['view_scope' => 'all']);
        $this->technician = User::factory()->create();
        $this->site = Site::factory()->create();
        $this->manager = User::factory()->schoolManager()->create();
        SiteManager::create(['site_id' => $this->site->id, 'user_id' => $this->manager->id, 'from' => now()->toDateString()]);
        $this->channel = IntakeChannel::create(['name' => 'الهاتف']);
        $this->type = RequestType::create(['name' => 'صيانة']);
    }

    protected function send(User $u, string $method, string $url, array $data = [], bool $idem = true): TestResponse
    {
        $this->signIn($u);
        if ($idem) {
            $this->withHeader('Idempotency-Key', uniqid('k', true));
        }

        return $this->json($method, $url, $data);
    }

    protected function act(User $u, string $url, array $data = []): TestResponse
    {
        return $this->send($u, 'POST', $url, $data);
    }

    protected function read(User $u, string $url): TestResponse
    {
        return $this->send($u, 'GET', $url, [], false);
    }

    protected function newRequest(?User $by = null, array $over = []): array
    {
        $by ??= $this->manager;
        $res = $this->act($by, '/api/v1/requests', $over + [
            'origin_site_id' => $this->site->id, 'channel_id' => $this->channel->id, 'description' => 'الجهاز لا يعمل',
        ] + ($by->role === 'secretary' ? ['requester_name' => 'متصل'] : []));
        $res->assertStatus(201);

        return $res->json('data');
    }

    /** Creates a request and drives it to the requested state. */
    protected function requestAt(string $state, ?User $assignee = null): int
    {
        $assignee ??= $this->engineer;
        $id = $this->newRequest()['id'];
        if ($state === 'new') {
            return $id;
        }
        $this->act($this->secretary, "/api/v1/requests/$id/triage", ['request_type_id' => $this->type->id, 'priority' => 'high'])->assertOk();
        if ($state === 'triaged') {
            return $id;
        }
        $this->act($this->secretary, "/api/v1/requests/$id/assignment", ['assignee_id' => $assignee->id])->assertOk();
        if ($state === 'assigned') {
            return $id;
        }
        $this->act($assignee, "/api/v1/requests/$id/start")->assertOk();
        if ($state === 'in_progress') {
            return $id;
        }
        if ($state === 'held') {
            $this->act($assignee, "/api/v1/requests/$id/hold", ['hold_reason' => 'بانتظار قطعة'])->assertOk();

            return $id;
        }
        $this->act($assignee, "/api/v1/requests/$id/close", ['closure_action' => 'استبدال', 'closure_result' => 'تم', 'closure_effort_minutes' => 30])->assertOk();

        return $id; // closed
    }

    protected function asset(?Site $site = null): Asset
    {
        $cat = DeviceCategory::firstOrCreate(['name' => 'حاسوب']);

        return Asset::create(['inventory_no' => 'INV-'.uniqid(), 'category_id' => $cat->id, 'current_site_id' => ($site ?? $this->site)->id]);
    }

    protected function taskType(bool $admin = false): TaskType
    {
        return TaskType::create(['name' => 'نوع '.uniqid(), 'is_administrative' => $admin, 'secretary_assignable' => $admin]);
    }
}
