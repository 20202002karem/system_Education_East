<?php

namespace Tests\Feature\M3;

use App\Models\Site;
use App\Models\User;

/** M3-API-017..026 — BR-M3-10..15. */
class TaskTest extends M3TestCase
{
    protected function task(array $over = [], ?User $by = null): array
    {
        return $this->act($by ?? $this->chairman, '/api/v1/tasks', $over + ['task_type_id' => $this->taskType()->id, 'title' => 'فحص مختبر', 'site_id' => $this->site->id])
            ->assertStatus(201)->json('data');
    }

    public function test_create_rules(): void
    {
        $t = $this->task();
        $this->assertMatchesRegularExpression('/^TSK-\d{4}-\d{6}$/', $t['ref_no']);
        $this->assertSame('new', $t['status']);
        $this->assertDatabaseHas('audit_log', ['action' => 'task.created', 'entity_id' => (string) $t['id']]);
        foreach ([$this->manager, $this->engineer, $this->technician] as $u) {
            $this->act($u, '/api/v1/tasks', ['task_type_id' => 1, 'title' => 'x', 'site_id' => 1])->assertStatus(403);
        }
        $this->act($this->chairman, '/api/v1/tasks', ['task_type_id' => $this->taskType()->id, 'title' => 'x'])->assertStatus(422); // standalone needs site
        $this->act($this->chairman, '/api/v1/tasks', ['task_type_id' => 9999, 'title' => 'x', 'site_id' => $this->site->id])->assertStatus(404);
        $this->act($this->chairman, '/api/v1/tasks', ['task_type_id' => $this->taskType()->id, 'title' => 'x', 'site_id' => $this->site->id, 'asset_id' => $this->asset(Site::factory()->create())->id])->assertStatus(422);
        $this->act($this->chairman, '/api/v1/tasks', ['task_type_id' => $this->taskType()->id, 'site_id' => $this->site->id])->assertStatus(422); // title
    }

    public function test_secretary_creates_administrative_tasks_only(): void
    {
        $this->act($this->secretary, '/api/v1/tasks', ['task_type_id' => $this->taskType(false)->id, 'title' => 'x', 'site_id' => $this->site->id])->assertStatus(403);
        $this->act($this->secretary, '/api/v1/tasks', ['task_type_id' => $this->taskType(true)->id, 'title' => 'x', 'site_id' => $this->site->id])->assertStatus(201);
    }

    public function test_full_lifecycle_and_result_summary_mandatory(): void
    {
        $t = $this->task();
        $id = $t['id'];
        $this->act($this->engineer, "/api/v1/tasks/$id/start")->assertStatus(403);
        $this->act($this->secretary, "/api/v1/tasks/$id/assignment", ['assignee_id' => $this->engineer->id])->assertOk()->assertJsonPath('data.status', 'assigned');
        $this->assertDatabaseHas('notifications', ['recipient_id' => $this->engineer->id, 'event_type' => 'task.assigned']);
        $this->act($this->technician, "/api/v1/tasks/$id/start")->assertStatus(404); // invisible to unassigned technician
        $this->act($this->engineer, "/api/v1/tasks/$id/complete", ['result_summary' => 'x'])->assertStatus(409); // not in progress
        $this->act($this->engineer, "/api/v1/tasks/$id/start")->assertOk()->assertJsonPath('data.status', 'in_progress');
        $this->act($this->engineer, "/api/v1/tasks/$id/hold")->assertOk()->assertJsonPath('data.status', 'held');
        $this->act($this->engineer, "/api/v1/tasks/$id/resume")->assertOk();
        $this->act($this->engineer, "/api/v1/tasks/$id/complete", [])->assertStatus(422);
        $this->act($this->engineer, "/api/v1/tasks/$id/complete", ['result_summary' => 'تم الفحص'])->assertOk()->assertJsonPath('data.status', 'completed');
        $this->act($this->engineer, "/api/v1/tasks/$id/complete", ['result_summary' => 'again'])->assertStatus(409);
        foreach (['started', 'held', 'resumed', 'completed', 'assigned'] as $e) {
            $this->assertDatabaseHas('audit_log', ['action' => "task.$e", 'entity_id' => (string) $id]);
        }
    }

    public function test_assignment_authority(): void
    {
        $id = $this->task()['id'];
        $this->act($this->secretary, "/api/v1/tasks/$id/assignment", ['assignee_id' => $this->secretary->id])->assertStatus(403);
        $this->act($this->secretary, "/api/v1/tasks/$id/assignment", ['assignee_id' => $this->chairman->id])->assertStatus(403);
        $this->act($this->secretary, "/api/v1/tasks/$id/assignment", ['assignee_id' => 9999])->assertStatus(404);
        $this->act($this->engineer, "/api/v1/tasks/$id/assignment", ['assignee_id' => $this->engineer->id])->assertStatus(403);
        $this->act($this->chairman, "/api/v1/tasks/$id/assignment", ['assignee_id' => $this->secretary->id])->assertStatus(422); // non-admin task
        $admin = $this->task(['task_type_id' => $this->taskType(true)->id])['id'];
        $this->act($this->chairman, "/api/v1/tasks/$admin/assignment", ['assignee_id' => $this->secretary->id])->assertOk();
        $this->act($this->secretary, "/api/v1/tasks/$admin/start")->assertOk();
        $this->act($this->secretary, "/api/v1/tasks/$admin/complete", ['result_summary' => 'تم'])->assertOk();
    }

    public function test_reassign_requires_reason(): void
    {
        $id = $this->task()['id'];
        $this->act($this->chairman, "/api/v1/tasks/$id/assignment", ['assignee_id' => $this->engineer->id])->assertOk();
        $this->act($this->chairman, "/api/v1/tasks/$id/assignment", ['assignee_id' => $this->technician->id])->assertStatus(422);
        $this->act($this->chairman, "/api/v1/tasks/$id/assignment", ['assignee_id' => $this->technician->id, 'reason' => 'توازن'])->assertOk();
        $this->assertDatabaseHas('audit_log', ['action' => 'task.reassigned', 'entity_id' => (string) $id]);
    }

    public function test_cancel_rules_secretary_vs_chairman(): void
    {
        $admin = $this->task(['task_type_id' => $this->taskType(true)->id])['id'];
        $this->act($this->chairman, "/api/v1/tasks/$admin/assignment", ['assignee_id' => $this->engineer->id])->assertOk();
        $this->act($this->secretary, "/api/v1/tasks/$admin/cancel", [])->assertStatus(422);
        $this->act($this->engineer, "/api/v1/tasks/$admin/cancel", ['reason' => 'x'])->assertStatus(403);
        $this->act($this->engineer, "/api/v1/tasks/$admin/start")->assertOk();
        $this->act($this->secretary, "/api/v1/tasks/$admin/cancel", ['reason' => 'x'])->assertStatus(403); // in progress
        $this->act($this->chairman, "/api/v1/tasks/$admin/cancel", ['reason' => 'x'])->assertStatus(422); // work done required after start
        $this->act($this->chairman, "/api/v1/tasks/$admin/cancel", ['reason' => 'x', 'work_done_summary' => 'جزئي'])->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->act($this->chairman, "/api/v1/tasks/$admin/cancel", ['reason' => 'x'])->assertStatus(409);

        $plain = $this->task()['id']; // non administrative: secretary cannot cancel
        $this->act($this->secretary, "/api/v1/tasks/$plain/cancel", ['reason' => 'x'])->assertStatus(403);
        $adm2 = $this->task(['task_type_id' => $this->taskType(true)->id])['id'];
        $this->act($this->secretary, "/api/v1/tasks/$adm2/cancel", ['reason' => 'ألغيت'])->assertOk();
    }

    public function test_visibility_and_my_tasks(): void
    {
        $mine = $this->task()['id'];
        $this->act($this->chairman, "/api/v1/tasks/$mine/assignment", ['assignee_id' => $this->technician->id])->assertOk();
        $other = $this->task(['site_id' => Site::factory()->create()->id])['id'];
        $this->read($this->manager, '/api/v1/tasks')->assertStatus(403);
        $this->read($this->manager, "/api/v1/tasks/$mine")->assertStatus(403);
        $this->assertSame([$mine], array_column($this->read($this->technician, '/api/v1/tasks')->json('data'), 'id'));
        $this->read($this->technician, "/api/v1/tasks/$other")->assertStatus(404);
        $this->assertCount(2, $this->read($this->chairman, '/api/v1/tasks')->json('data'));
        $this->assertSame([$mine], array_column($this->read($this->chairman, "/api/v1/tasks?assignee_id={$this->technician->id}&status=assigned")->json('data'), 'id'));
        $this->assertSame([$mine], array_column($this->read($this->chairman, '/api/v1/tasks?q=TSK&sort=due_at&assignee_id='.$this->technician->id)->json('data'), 'id'));
        $this->read($this->chairman, '/api/v1/tasks?status=bogus')->assertStatus(422);
        $this->read($this->chairman, '/api/v1/tasks?per_page=1')->assertJsonPath('meta.total', 2);
    }

    public function test_task_proposals_and_accept(): void
    {
        $id = $this->task()['id'];
        $this->act($this->chairman, "/api/v1/tasks/$id/assignment", ['assignee_id' => $this->engineer->id])->assertOk();
        $this->act($this->technician, "/api/v1/tasks/$id/proposals", ['type' => 'reassign', 'reason' => 'r'])->assertStatus(404);
        $p = $this->act($this->engineer, "/api/v1/tasks/$id/proposals", ['type' => 'reassign', 'reason' => 'مشغول'])->assertStatus(201)->json('data');
        $this->assertSame('task', $p['subject_type']);
        $this->assertDatabaseHas('audit_log', ['action' => 'task.reassign_proposed']);
        $this->act($this->secretary, "/api/v1/proposals/{$p['id']}/accept", ['new_assignee_id' => $this->technician->id])->assertOk();
        $this->assertDatabaseHas('tasks', ['id' => $id, 'assignee_id' => $this->technician->id, 'status' => 'assigned']);
        $c = $this->act($this->technician, "/api/v1/tasks/$id/proposals", ['type' => 'cancel', 'reason' => 'r', 'work_done_summary' => 'لا شيء'])->assertStatus(201)->json('data');
        $this->act($this->chairman, "/api/v1/proposals/{$c['id']}/accept")->assertOk();
        $this->assertDatabaseHas('tasks', ['id' => $id, 'status' => 'cancelled']);
    }

    public function test_task_version_conflict(): void
    {
        $t = $this->task();
        $this->act($this->chairman, "/api/v1/tasks/{$t['id']}/assignment", ['assignee_id' => $this->engineer->id, 'version' => 99])->assertStatus(409)->assertJsonPath('error.code', 'version_conflict');
        $this->act($this->chairman, "/api/v1/tasks/{$t['id']}/assignment", ['assignee_id' => $this->engineer->id, 'version' => $t['version']])->assertOk();
    }

    public function test_task_linked_to_cycle_uses_request_site_and_blocks_terminal_cycle(): void
    {
        $rid = $this->requestAt('in_progress');
        $cycle = \App\Models\RequestCycle::where('request_id', $rid)->first();
        $t = $this->act($this->chairman, '/api/v1/tasks', ['task_type_id' => $this->taskType()->id, 'title' => 'x', 'request_cycle_id' => $cycle->id])->assertStatus(201)->json('data');
        $this->assertSame($this->site->id, $t['site_id']);
        $closed = \App\Models\RequestCycle::where('request_id', $this->requestAt('closed'))->first();
        $this->act($this->chairman, '/api/v1/tasks', ['task_type_id' => $this->taskType()->id, 'title' => 'x', 'request_cycle_id' => $closed->id])->assertStatus(409);
    }
}
