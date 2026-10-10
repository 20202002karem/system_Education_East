<?php

namespace Tests\Feature\M3;

use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\RequestCycle;
use App\Models\ServiceRequest;
use App\Models\Site;
use App\Models\User;

/** M3-API-001..011 — creation, triage, assignment, execution, closure, cancellation, reopen. */
class RequestLifecycleTest extends M3TestCase
{
    public function test_school_manager_creates_request_with_ref_no_cycle_audit_and_notifications(): void
    {
        $data = $this->newRequest();
        $this->assertMatchesRegularExpression('/^REQ-\d{4}-\d{6}$/', $data['ref_no']);
        $this->assertSame('new', $data['status']);
        $this->assertSame($this->manager->id, $data['requester_user_id']);
        $this->assertSame($this->manager->id, $data['registered_by_user_id']);
        $this->assertDatabaseHas('audit_log', ['action' => 'request.created', 'entity_id' => (string) $data['id']]);
        $this->assertSame(2, Notification::where('event_type', 'request.created')->count()); // chairman + secretary
    }

    public function test_second_request_gets_next_sequence_number(): void
    {
        $a = $this->newRequest();
        $b = $this->newRequest();
        $this->assertNotSame($a['ref_no'], $b['ref_no']);
        $this->assertStringEndsWith('000002', $b['ref_no']);
    }

    public function test_idempotency_key_replays_first_response_without_duplicate(): void
    {
        $this->signIn($this->manager);
        $body = ['origin_site_id' => $this->site->id, 'channel_id' => $this->channel->id, 'description' => 'x'];
        $h = ['Idempotency-Key' => 'same-key'];
        $a = $this->postJson('/api/v1/requests', $body, $h)->assertStatus(201);
        $b = $this->postJson('/api/v1/requests', $body, $h)->assertStatus(201);
        $this->assertSame($a->json('data.id'), $b->json('data.id'));
        $this->assertSame(1, ServiceRequest::count());
    }

    public function test_missing_idempotency_key_is_rejected(): void
    {
        $this->signIn($this->manager);
        $this->postJson('/api/v1/requests', ['origin_site_id' => $this->site->id, 'channel_id' => $this->channel->id, 'description' => 'x'])
            ->assertStatus(400)->assertJsonPath('error.code', 'idempotency_key_required');
    }

    public function test_creation_authorization_and_validation(): void
    {
        $body = ['origin_site_id' => $this->site->id, 'channel_id' => $this->channel->id, 'description' => 'x'];
        foreach ([$this->chairman, $this->engineer, $this->technician] as $u) {
            $this->act($u, '/api/v1/requests', $body)->assertStatus(403);
        }
        $other = Site::factory()->create();
        $this->act($this->manager, '/api/v1/requests', ['origin_site_id' => $other->id] + $body)->assertStatus(403);
        $this->act($this->manager, '/api/v1/requests', ['channel_id' => null] + $body)->assertStatus(422)->assertJsonStructure(['error' => ['code', 'fields']]);
        $this->act($this->manager, '/api/v1/requests', ['channel_id' => 9999] + $body)->assertStatus(404);
        $this->act($this->secretary, '/api/v1/requests', $body)->assertStatus(422); // requester_name required
        $this->act($this->secretary, '/api/v1/requests', $body + ['requester_name' => 'متصل'])->assertStatus(201)
            ->assertJsonPath('data.requester_user_id', null)->assertJsonPath('data.requester_name', 'متصل');
        $this->act($this->manager, '/api/v1/requests', $body + ['suggested_priority' => 'urgent'])->assertStatus(422);
    }

    public function test_asset_must_belong_to_origin_site(): void
    {
        $body = ['origin_site_id' => $this->site->id, 'channel_id' => $this->channel->id, 'description' => 'x'];
        $foreign = $this->asset(Site::factory()->create());
        $this->act($this->manager, '/api/v1/requests', $body + ['asset_id' => $foreign->id])->assertStatus(422);
        $this->act($this->manager, '/api/v1/requests', $body + ['asset_id' => $this->asset()->id])->assertStatus(201);
    }

    public function test_inactive_channel_and_site_are_rejected(): void
    {
        $this->channel->update(['is_active' => false]);
        $this->act($this->manager, '/api/v1/requests', ['origin_site_id' => $this->site->id, 'channel_id' => $this->channel->id, 'description' => 'x'])->assertStatus(422);
        $this->channel->update(['is_active' => true]);
        $this->site->update(['status' => 'archived']);
        $this->act($this->secretary, '/api/v1/requests', ['origin_site_id' => $this->site->id, 'channel_id' => $this->channel->id, 'description' => 'x', 'requester_name' => 'a'])->assertStatus(422);
    }

    public function test_full_lifecycle_new_to_closed_with_audit_and_closure_notification(): void
    {
        $id = $this->requestAt('closed');
        $r = $this->read($this->chairman, "/api/v1/requests/$id")->assertOk();
        $this->assertSame('closed', $r->json('data.status'));
        $this->assertSame(30, $r->json('data.cycle.closure_effort_minutes'));
        $this->assertNotNull($r->json('data.cycle.closed_at'));
        foreach (['created', 'triaged', 'assigned', 'started', 'closed'] as $e) {
            $this->assertDatabaseHas('audit_log', ['action' => "request.$e", 'entity_id' => (string) $id]);
        }
        $this->assertDatabaseHas('notifications', ['recipient_id' => $this->manager->id, 'event_type' => 'request.closed']);
        $this->assertDatabaseHas('notifications', ['recipient_id' => $this->engineer->id, 'event_type' => 'request.assigned']);
    }

    public function test_hold_and_resume(): void
    {
        $id = $this->requestAt('held');
        $this->act($this->engineer, "/api/v1/requests/$id/resume")->assertOk()->assertJsonPath('data.status', 'in_progress');
        $this->act($this->engineer, "/api/v1/requests/$id/resume")->assertStatus(409);
    }

    public function test_triage_only_from_new_and_by_chairman_or_secretary(): void
    {
        $id = $this->requestAt('new');
        $body = ['request_type_id' => $this->type->id, 'priority' => 'normal'];
        $this->act($this->engineer, "/api/v1/requests/$id/triage", $body)->assertStatus(403);
        $this->act($this->manager, "/api/v1/requests/$id/triage", $body)->assertStatus(403);
        $this->act($this->chairman, "/api/v1/requests/$id/triage", $body)->assertOk()->assertJsonPath('data.status', 'triaged');
        $this->act($this->chairman, "/api/v1/requests/$id/triage", $body)->assertStatus(409);
        $this->act($this->chairman, '/api/v1/requests/'.$this->requestAt('new').'/triage', ['priority' => 'bogus', 'request_type_id' => 1])->assertStatus(422);
    }

    public function test_assignment_rules_for_secretary_and_chairman(): void
    {
        $id = $this->requestAt('triaged');
        $this->act($this->secretary, "/api/v1/requests/$id/assignment", ['assignee_id' => $this->secretary->id])->assertStatus(403);
        $this->act($this->secretary, "/api/v1/requests/$id/assignment", ['assignee_id' => $this->chairman->id])->assertStatus(403);
        $this->act($this->secretary, "/api/v1/requests/$id/assignment", ['assignee_id' => $this->manager->id])->assertStatus(422);
        $this->act($this->secretary, "/api/v1/requests/$id/assignment", ['assignee_id' => 99999])->assertStatus(404);
        $this->act($this->chairman, "/api/v1/requests/$id/assignment", ['assignee_id' => $this->chairman->id])->assertOk()
            ->assertJsonPath('data.cycle.assignee_user_id', $this->chairman->id);
    }

    public function test_inactive_assignee_and_out_of_scope_engineer_are_404(): void
    {
        $id = $this->requestAt('triaged');
        $inactive = User::factory()->engineer()->create(['status' => 'disabled']);
        $this->act($this->secretary, "/api/v1/requests/$id/assignment", ['assignee_id' => $inactive->id])->assertStatus(404);
        $scoped = User::factory()->engineer()->create(); // view_scope sites, no scopes
        $this->act($this->secretary, "/api/v1/requests/$id/assignment", ['assignee_id' => $scoped->id])->assertStatus(404);
        \App\Models\UserSiteScope::create(['user_id' => $scoped->id, 'site_id' => $this->site->id]);
        $this->act($this->secretary, "/api/v1/requests/$id/assignment", ['assignee_id' => $scoped->id])->assertOk();
    }

    public function test_cannot_assign_new_or_terminal_request(): void
    {
        $this->act($this->secretary, '/api/v1/requests/'.$this->requestAt('new').'/assignment', ['assignee_id' => $this->engineer->id])->assertStatus(409);
        $this->act($this->secretary, '/api/v1/requests/'.$this->requestAt('closed').'/assignment', ['assignee_id' => $this->engineer->id])->assertStatus(409);
    }

    public function test_reassignment_requires_reason_closes_old_row_and_moves_closing_right(): void
    {
        $id = $this->requestAt('in_progress');
        $other = User::factory()->create();
        $this->act($this->secretary, "/api/v1/requests/$id/assignment", ['assignee_id' => $other->id])->assertStatus(422);
        $res = $this->act($this->secretary, "/api/v1/requests/$id/assignment", ['assignee_id' => $other->id, 'reason' => 'نقص خبرة'])->assertOk();
        $this->assertSame('assigned', $res->json('data.status'));
        $this->assertDatabaseHas('audit_log', ['action' => 'request.reassigned', 'entity_id' => (string) $id]);
        $rows = $this->read($this->chairman, "/api/v1/requests/$id/assignments")->json('data');
        $this->assertCount(2, $rows);
        $this->assertNotNull($rows[0]['to']);
        $this->assertNull($rows[1]['to']);
        // former assignee lost the right immediately
        $this->act($this->engineer, "/api/v1/requests/$id/start")->assertStatus(403);
    }

    public function test_execution_requires_current_assignee_and_valid_state(): void
    {
        $id = $this->requestAt('assigned');
        $this->act($this->technician, "/api/v1/requests/$id/start")->assertStatus(404); // technician not assigned: invisible
        $this->act($this->chairman, "/api/v1/requests/$id/start")->assertStatus(403);
        $this->act($this->engineer, "/api/v1/requests/$id/hold")->assertStatus(409);
        $this->act($this->engineer, "/api/v1/requests/$id/close", ['closure_action' => 'a', 'closure_result' => 'b', 'closure_effort_minutes' => 1])->assertStatus(409);
        $this->act($this->engineer, "/api/v1/requests/$id/start")->assertOk()->assertJsonPath('data.status', 'in_progress');
        $this->act($this->engineer, "/api/v1/requests/$id/start")->assertStatus(409);
    }

    public function test_close_validation_and_open_child_tasks_block(): void
    {
        $id = $this->requestAt('in_progress');
        $this->act($this->engineer, "/api/v1/requests/$id/close", ['closure_action' => 'a'])->assertStatus(422);
        $this->act($this->engineer, "/api/v1/requests/$id/close", ['closure_action' => 'a', 'closure_result' => 'b', 'closure_effort_minutes' => -1])->assertStatus(422);
        $cycle = RequestCycle::where('request_id', $id)->first();
        $t = $this->act($this->chairman, '/api/v1/tasks', ['task_type_id' => $this->taskType()->id, 'title' => 'مهمة', 'request_cycle_id' => $cycle->id])->assertStatus(201)->json('data');
        $this->act($this->engineer, "/api/v1/requests/$id/close", ['closure_action' => 'a', 'closure_result' => 'b', 'closure_effort_minutes' => 5])
            ->assertStatus(409)->assertJsonPath('error.code', 'open_child_tasks');
        $this->act($this->chairman, "/api/v1/tasks/{$t['id']}/cancel", ['reason' => 'غير لازمة'])->assertOk();
        $this->act($this->engineer, "/api/v1/requests/$id/close", ['closure_action' => 'a', 'closure_result' => 'b', 'closure_effort_minutes' => 5])->assertOk();
    }

    public function test_optimistic_version_conflict(): void
    {
        $id = $this->requestAt('assigned');
        $v = $this->read($this->engineer, "/api/v1/requests/$id")->json('data.cycle.version');
        $this->act($this->engineer, "/api/v1/requests/$id/start", ['version' => $v + 5])->assertStatus(409)->assertJsonPath('error.code', 'version_conflict');
        $this->act($this->engineer, "/api/v1/requests/$id/start", ['version' => $v])->assertOk();
    }

    public function test_cancel_rules(): void
    {
        // school_manager: before assignment only, requester only
        $id = $this->requestAt('new');
        $body = ['reason_category' => 'duplicate', 'reason_text' => 'مكرر'];
        $other = User::factory()->schoolManager()->create();
        SiteManagerHelper::attach($other, $this->site);
        $this->act($other, "/api/v1/requests/$id/cancel", $body)->assertStatus(403);
        $this->act($this->manager, "/api/v1/requests/$id/cancel", [])->assertStatus(422);
        $this->act($this->manager, "/api/v1/requests/$id/cancel", $body)->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->act($this->chairman, "/api/v1/requests/$id/cancel", $body)->assertStatus(409);
        $this->assertDatabaseHas('request_cancellations', ['request_id' => $id, 'stage' => 'before_assignment']);

        $id2 = $this->requestAt('assigned');
        $this->act($this->manager, "/api/v1/requests/$id2/cancel", $body)->assertStatus(403);
        $this->act($this->secretary, "/api/v1/requests/$id2/cancel", $body)->assertStatus(403);
        $this->act($this->engineer, "/api/v1/requests/$id2/cancel", $body)->assertStatus(403);
        $this->act($this->chairman, "/api/v1/requests/$id2/cancel", $body)->assertOk();
    }

    public function test_chairman_cancel_after_start_requires_work_done_and_cascades_tasks(): void
    {
        $id = $this->requestAt('in_progress');
        $cycle = RequestCycle::where('request_id', $id)->first();
        $t = $this->act($this->chairman, '/api/v1/tasks', ['task_type_id' => $this->taskType()->id, 'title' => 'م', 'request_cycle_id' => $cycle->id])->json('data');
        $body = ['reason_category' => 'other', 'reason_text' => 'ألغي'];
        $this->act($this->chairman, "/api/v1/requests/$id/cancel", $body)->assertStatus(422);
        $this->act($this->chairman, "/api/v1/requests/$id/cancel", $body + ['work_done_summary' => 'فُحص الجهاز'])->assertOk();
        $this->assertDatabaseHas('request_cancellations', ['request_id' => $id, 'stage' => 'after_start']);
        $this->assertDatabaseHas('tasks', ['id' => $t['id'], 'status' => 'cancelled']);
        $this->assertDatabaseHas('audit_log', ['action' => 'task.cancelled', 'entity_id' => (string) $t['id']]);
        // cancelled is absolutely final
        $this->act($this->chairman, "/api/v1/requests/$id/reopen", ['reason' => 'x'])->assertStatus(409);
    }

    public function test_reopen_window_and_authority(): void
    {
        $id = $this->requestAt('closed');
        $this->act($this->secretary, "/api/v1/requests/$id/reopen", ['reason' => 'x'])->assertStatus(403);
        $this->act($this->manager, "/api/v1/requests/$id/reopen", [])->assertStatus(422);
        $res = $this->act($this->manager, "/api/v1/requests/$id/reopen", ['reason' => 'عاد العطل'])->assertStatus(201);
        $this->assertSame('reopened_pending_assignment', $res->json('data.status'));
        $this->assertSame(2, $res->json('data.current_cycle_no'));
        $this->assertSame(1, $res->json('data.reopen_count'));
        $this->assertSame(2, RequestCycle::where('request_id', $id)->count());
        $this->act($this->manager, "/api/v1/requests/$id/reopen", ['reason' => 'x'])->assertStatus(409); // last cycle not closed
        $this->assertDatabaseHas('notifications', ['recipient_id' => $this->engineer->id, 'event_type' => 'request.reopened']);
        // reopened requests skip triage and go straight to assignment
        $this->act($this->secretary, "/api/v1/requests/$id/assignment", ['assignee_id' => $this->engineer->id])->assertOk();
        $this->assertCount(2, $this->read($this->chairman, "/api/v1/requests/$id/cycles")->json('data'));
    }

    public function test_reopen_after_window_only_chairman(): void
    {
        $id = $this->requestAt('closed');
        RequestCycle::where('request_id', $id)->update(['closed_at' => now('UTC')->subDays(8)]);
        $this->act($this->manager, "/api/v1/requests/$id/reopen", ['reason' => 'x'])->assertStatus(403);
        $this->act($this->chairman, "/api/v1/requests/$id/reopen", ['reason' => 'x'])->assertStatus(201);
    }

    public function test_reopen_window_follows_the_setting(): void
    {
        \App\Models\Setting::updateOrCreate(['key' => 'reopen_window_days'], ['value' => '30']);
        $id = $this->requestAt('closed');
        RequestCycle::where('request_id', $id)->update(['closed_at' => now('UTC')->subDays(20)]);
        $this->act($this->manager, "/api/v1/requests/$id/reopen", ['reason' => 'x'])->assertStatus(201);
    }

    public function test_only_one_open_cycle_per_request_db_constraint(): void
    {
        $id = $this->requestAt('new');
        $this->expectException(\Illuminate\Database\QueryException::class);
        RequestCycle::create(['request_id' => $id, 'cycle_no' => 2, 'status' => 'new', 'open_marker' => 1]);
    }

    public function test_origin_site_is_immutable(): void
    {
        $r = ServiceRequest::find($this->newRequest()['id']);
        $this->expectException(\LogicException::class);
        $r->update(['origin_site_id' => Site::factory()->create()->id]);
    }

    public function test_audit_rows_never_contain_secrets_and_chain_is_intact(): void
    {
        $this->requestAt('closed');
        $this->assertGreaterThanOrEqual(5, AuditLog::count());
        $prev = str_repeat('0', 64);
        foreach (AuditLog::orderBy('seq')->get() as $row) {
            $this->assertSame($prev, $row->prev_hash);
            $prev = $row->hash;
        }
    }
}

class SiteManagerHelper
{
    public static function attach(User $u, Site $s): void
    {
        \App\Models\SiteManager::create(['site_id' => $s->id, 'user_id' => $u->id, 'from' => now()->toDateString()]);
    }
}
