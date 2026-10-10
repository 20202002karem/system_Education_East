<?php

namespace Tests\Feature\M3;

use App\Models\Site;
use App\Models\User;
use App\Models\UserSiteScope;

/** Scope isolation (IDOR → 404), notes visibility (BR-M3-05), listing, proposals (DD-M3-01). */
class ScopeNotesProposalsTest extends M3TestCase
{
    public function test_school_manager_sees_only_own_site_requests_and_foreign_id_is_404(): void
    {
        $mine = $this->newRequest()['id'];
        $otherSite = Site::factory()->create();
        $foreign = $this->newRequest($this->secretary, ['origin_site_id' => $otherSite->id])['id'];

        $list = $this->read($this->manager, '/api/v1/requests')->assertOk();
        $this->assertSame([$mine], array_column($list->json('data'), 'id'));
        $this->assertSame(1, $list->json('meta.total'));
        foreach (["", "/cycles", "/assignments", "/notes"] as $sfx) {
            $this->read($this->manager, "/api/v1/requests/$foreign$sfx")->assertStatus(404);
        }
        $this->act($this->manager, "/api/v1/requests/$foreign/notes", ['visibility' => 'external', 'body' => 'x'])->assertStatus(404);
        $this->act($this->manager, "/api/v1/requests/$foreign/cancel", ['reason_category' => 'a', 'reason_text' => 'b'])->assertStatus(404);
        $this->act($this->manager, "/api/v1/requests/$foreign/reopen", ['reason' => 'a'])->assertStatus(404);
        $this->assertCount(2, $this->read($this->chairman, '/api/v1/requests')->json('data'));
    }

    public function test_technician_sees_only_assigned_and_engineer_scope_plus_assigned(): void
    {
        $id = $this->requestAt('assigned', $this->technician);
        $other = $this->newRequest()['id'];
        $this->assertSame([$id], array_column($this->read($this->technician, '/api/v1/requests')->json('data'), 'id'));
        $this->read($this->technician, "/api/v1/requests/$other")->assertStatus(404);
        $this->read($this->technician, "/api/v1/requests/$id")->assertOk();

        $eng = User::factory()->engineer()->create(); // 'sites' scope, none yet
        $this->assertCount(0, $this->read($eng, '/api/v1/requests')->json('data'));
        UserSiteScope::create(['user_id' => $eng->id, 'site_id' => $this->site->id]);
        $this->assertCount(2, $this->read($eng, '/api/v1/requests')->json('data'));
    }

    public function test_list_filters_search_sort_and_pagination(): void
    {
        $a = $this->newRequest(null, ['description' => 'طابعة معطلة'])['id'];
        $b = $this->requestAt('triaged');
        $meta = $this->read($this->chairman, '/api/v1/requests?per_page=1&page=2')->assertOk()->json('meta');
        $this->assertSame(['page' => 2, 'per_page' => 1, 'total' => 2], $meta);
        $this->assertSame([$a], array_column($this->read($this->chairman, '/api/v1/requests?q=طابعة')->json('data'), 'id'));
        $this->assertSame([$b], array_column($this->read($this->chairman, '/api/v1/requests?status=triaged')->json('data'), 'id'));
        $this->assertSame([$b], array_column($this->read($this->chairman, '/api/v1/requests?priority=high&sort=-priority')->json('data'), 'id'));
        $this->read($this->chairman, '/api/v1/requests?status=bogus')->assertStatus(422);
        $this->read($this->chairman, '/api/v1/requests?sort=description')->assertStatus(422);
        $this->read($this->chairman, '/api/v1/requests?per_page=500')->assertOk()->assertJsonPath('meta.per_page', 100);
    }

    public function test_internal_notes_hidden_from_school_manager_server_side(): void
    {
        $id = $this->requestAt('assigned');
        $this->act($this->manager, "/api/v1/requests/$id/notes", ['visibility' => 'internal', 'body' => 's'])->assertStatus(403);
        $this->act($this->manager, "/api/v1/requests/$id/notes", ['visibility' => 'external', 'body' => 'تحديث'])->assertStatus(201);
        $this->act($this->engineer, "/api/v1/requests/$id/notes", ['visibility' => 'internal', 'body' => 'سري'])->assertStatus(201);
        $this->act($this->engineer, "/api/v1/requests/$id/notes", ['visibility' => 'external', 'body' => 'رد'])->assertStatus(201);
        $this->act($this->engineer, "/api/v1/requests/$id/notes", ['visibility' => 'weird', 'body' => 'x'])->assertStatus(422);
        $this->act($this->engineer, "/api/v1/requests/$id/notes", ['visibility' => 'internal', 'body' => ''])->assertStatus(422);

        $mgr = $this->read($this->manager, "/api/v1/requests/$id/notes")->assertOk();
        $this->assertSame(['external'], array_unique(array_column($mgr->json('data'), 'visibility')));
        $this->assertStringNotContainsString('سري', $mgr->getContent());
        $this->assertCount(3, $this->read($this->engineer, "/api/v1/requests/$id/notes")->json('data'));
        // notes never change state
        $this->assertSame('assigned', $this->read($this->chairman, "/api/v1/requests/$id")->json('data.status'));
    }

    public function test_proposal_reassign_accept_flow(): void
    {
        $id = $this->requestAt('in_progress');
        $body = ['type' => 'reassign', 'reason' => 'خارج تخصصي'];
        $this->act($this->technician, "/api/v1/requests/$id/proposals", $body)->assertStatus(404); // invisible
        $this->act($this->secretary, "/api/v1/requests/$id/proposals", $body)->assertStatus(403);
        $this->act($this->chairman, "/api/v1/requests/$id/proposals", $body)->assertStatus(403);
        $this->act($this->engineer, "/api/v1/requests/$id/proposals", ['type' => 'x', 'reason' => 'r'])->assertStatus(422);
        $this->act($this->engineer, "/api/v1/requests/$id/proposals", ['type' => 'cancel', 'reason' => 'r'])->assertStatus(422); // work_done required
        $p = $this->act($this->engineer, "/api/v1/requests/$id/proposals", $body)->assertStatus(201)->json('data');
        $this->assertSame('pending', $p['status']);
        $this->assertSame('in_progress', $this->read($this->chairman, "/api/v1/requests/$id")->json('data.status')); // no state change
        $this->assertDatabaseHas('audit_log', ['action' => 'request.reassign_proposed']);
        $this->assertSame(1, $this->read($this->secretary, '/api/v1/proposals')->json('meta.total'));
        $this->read($this->engineer, '/api/v1/proposals')->assertStatus(403);

        $this->act($this->secretary, "/api/v1/proposals/{$p['id']}/accept", [])->assertStatus(422);
        $new = User::factory()->create();
        $this->act($this->secretary, "/api/v1/proposals/{$p['id']}/accept", ['new_assignee_id' => $new->id])->assertOk()->assertJsonPath('data.status', 'accepted');
        $this->assertDatabaseHas('request_cycles', ['request_id' => $id, 'assignee_user_id' => $new->id, 'status' => 'assigned']);
        $this->assertDatabaseHas('proposals', ['id' => $p['id'], 'decided_by' => $this->secretary->id]);
        $this->act($this->secretary, "/api/v1/proposals/{$p['id']}/accept", ['new_assignee_id' => $new->id])->assertStatus(409);
        $this->act($this->secretary, "/api/v1/proposals/{$p['id']}/reject")->assertStatus(409);
    }

    public function test_proposal_cancel_accept_and_reject(): void
    {
        $id = $this->requestAt('in_progress');
        $p = $this->act($this->engineer, "/api/v1/requests/$id/proposals", ['type' => 'cancel', 'reason' => 'غير ممكن', 'work_done_summary' => 'فحص'])->assertStatus(201)->json('data');
        $this->act($this->secretary, "/api/v1/proposals/{$p['id']}/accept")->assertStatus(403); // secretary cannot cancel after assignment
        $this->act($this->chairman, "/api/v1/proposals/{$p['id']}/accept")->assertOk();
        $this->assertDatabaseHas('request_cancellations', ['request_id' => $id, 'stage' => 'after_start']);

        $id2 = $this->requestAt('in_progress');
        $p2 = $this->act($this->engineer, "/api/v1/requests/$id2/proposals", ['type' => 'reassign', 'reason' => 'r'])->json('data');
        $this->act($this->chairman, "/api/v1/proposals/{$p2['id']}/reject", ['decision_reason' => 'لا'])->assertOk()->assertJsonPath('data.status', 'rejected');
        $this->assertSame('in_progress', $this->read($this->chairman, "/api/v1/requests/$id2")->json('data.status'));
        $this->read($this->chairman, '/api/v1/proposals?status=rejected&subject_type=request')->assertOk()->assertJsonPath('meta.total', 1);
    }

    public function test_proposal_requires_current_assignee_and_active_state(): void
    {
        $id = $this->requestAt('assigned', $this->technician);
        $second = User::factory()->engineer()->create(['view_scope' => 'all']);
        $this->act($second, "/api/v1/requests/$id/proposals", ['type' => 'reassign', 'reason' => 'r'])->assertStatus(403);
    }

    public function test_cycles_and_assignments_listing(): void
    {
        $id = $this->requestAt('closed');
        $this->act($this->chairman, "/api/v1/requests/$id/reopen", ['reason' => 'x'])->assertStatus(201);
        $this->act($this->chairman, "/api/v1/requests/$id/assignment", ['assignee_id' => $this->engineer->id])->assertOk();
        $cycles = $this->read($this->manager, "/api/v1/requests/$id/cycles")->assertOk()->json('data');
        $this->assertSame([1, 2], array_column($cycles, 'cycle_no'));
        $this->assertCount(2, $this->read($this->manager, "/api/v1/requests/$id/assignments")->json('data'));
        $this->assertCount(1, $this->read($this->manager, "/api/v1/requests/$id/assignments?cycle_id={$cycles[1]['id']}")->json('data'));
    }

    public function test_unauthenticated_requests_are_401(): void
    {
        $this->getJson('/api/v1/requests')->assertStatus(401);
        $this->getJson('/api/v1/tasks')->assertStatus(401);
        $this->getJson('/api/v1/notifications')->assertStatus(401);
    }
}
