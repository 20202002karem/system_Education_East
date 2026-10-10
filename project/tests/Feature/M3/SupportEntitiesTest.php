<?php

namespace Tests\Feature\M3;

use App\Models\Attachment;
use App\Models\Draft;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** Attachments (M3-API-030/031/037), drafts (032-034), notifications (035/036), read-only lookups. */
class SupportEntitiesTest extends M3TestCase
{
    protected function upload(User $u, string $type, int $id, ?UploadedFile $f = null)
    {
        $this->signIn($u)->withHeader('Idempotency-Key', uniqid('k', true));

        return $this->post('/api/v1/attachments', ['owner_type' => $type, 'owner_id' => $id, 'file' => $f ?? UploadedFile::fake()->create('report.pdf', 20, 'application/pdf')], ['Accept' => 'application/json']);
    }

    public function test_upload_list_and_signed_download(): void
    {
        Storage::fake('local');
        $id = $this->newRequest()['id'];
        $res = $this->upload($this->manager, 'request', $id)->assertStatus(201);
        $a = $res->json('data');
        $this->assertSame(64, strlen($a['sha256']));
        $this->assertSame('report.pdf', $a['original_filename']);
        $this->assertArrayNotHasKey('storage_path', $a);
        $this->assertDatabaseHas('audit_log', ['action' => 'attachment.uploaded', 'entity_id' => (string) $a['id']]);

        $this->read($this->chairman, "/api/v1/attachments?owner_type=request&owner_id=$id")->assertOk()->assertJsonPath('meta.total', 1);
        $this->read($this->chairman, '/api/v1/attachments')->assertStatus(422);
        $this->read($this->chairman, "/api/v1/attachments?owner_type=request")->assertStatus(422);
        $this->read($this->chairman, "/api/v1/attachments?owner_type=request&owner_id=9999")->assertStatus(404);

    }

    public function test_download_returns_temporary_signed_link_and_file_is_private(): void
    {
        Storage::fake('local');
        $id = $this->newRequest()['id'];
        $a = $this->upload($this->manager, 'request', $id)->json('data');
        $res = $this->read($this->chairman, "/api/v1/attachments/{$a['id']}/download")->assertOk();
        $url = $res->json('data.url');
        $this->assertStringContainsString('signature=', $url);
        $this->assertNotNull($res->json('data.expires_at'));
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $this->get($url)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get(preg_replace('/signature=[^&]+/', 'signature=bad', $url))->assertStatus(403);
        $this->get("/api/v1/attachments/{$a['id']}/file")->assertStatus(403); // unsigned
        // other site's manager cannot even obtain a link
        $other = User::factory()->schoolManager()->create();
        $this->read($other, "/api/v1/attachments/{$a['id']}/download")->assertStatus(404);
    }

    public function test_internal_note_attachment_hidden_from_school_manager_and_author_only(): void
    {
        Storage::fake('local');
        $id = $this->requestAt('assigned');
        $note = $this->act($this->engineer, "/api/v1/requests/$id/notes", ['visibility' => 'internal', 'body' => 'سري'])->json('data');
        $this->upload($this->chairman, 'request_note', $note['id'])->assertStatus(403); // not the author
        $a = $this->upload($this->engineer, 'request_note', $note['id'])->assertStatus(201)->json('data');
        $this->read($this->manager, "/api/v1/attachments?owner_type=request_note&owner_id={$note['id']}")->assertStatus(404);
        $this->read($this->manager, "/api/v1/attachments/{$a['id']}/download")->assertStatus(404);
        $this->upload($this->manager, 'request_note', $note['id'])->assertStatus(404);
        $this->read($this->chairman, "/api/v1/attachments?owner_type=request_note&owner_id={$note['id']}")->assertOk();
    }

    public function test_task_attachments_scope_and_no_delete_or_replace(): void
    {
        Storage::fake('local');
        $t = $this->act($this->chairman, '/api/v1/tasks', ['task_type_id' => $this->taskType()->id, 'title' => 'x', 'site_id' => $this->site->id])->json('data');
        $this->upload($this->technician, 'task', $t['id'])->assertStatus(404);
        $this->upload($this->manager, 'task', $t['id'])->assertStatus(404);
        $a = $this->upload($this->chairman, 'task', $t['id'])->assertStatus(201)->json('data');
        $this->assertContains($this->call2('DELETE', "/api/v1/attachments/{$a['id']}"), [404, 405]);
        $this->assertContains($this->call2('PUT', "/api/v1/attachments/{$a['id']}"), [404, 405]);
        $this->assertContains($this->call2('PATCH', "/api/v1/attachments/{$a['id']}"), [404, 405]);
        $this->assertSame(1, Attachment::count());
    }

    protected function call2(string $method, string $url): int
    {
        return $this->signIn($this->chairman)->json($method, $url)->status();
    }

    public function test_upload_validation(): void
    {
        $this->signIn($this->manager)->withHeader('Idempotency-Key', 'k1');
        $this->postJson('/api/v1/attachments', ['owner_type' => 'asset', 'owner_id' => 1])->assertStatus(422);
        $this->signIn($this->manager)->withHeader('Idempotency-Key', 'k2');
        $this->postJson('/api/v1/attachments', ['owner_type' => 'request', 'owner_id' => 1])->assertStatus(422); // file missing
    }

    public function test_drafts_upsert_list_delete_and_isolation(): void
    {
        $body = ['form_type' => 'request_create', 'payload' => ['description' => 'مسودة']];
        $a = $this->read($this->manager, '/api/v1/drafts')->assertOk();
        $this->assertCount(0, $a->json('data'));
        $d1 = $this->send($this->manager, 'PUT', '/api/v1/drafts', $body, false)->assertOk()->json('data');
        $d2 = $this->send($this->manager, 'PUT', '/api/v1/drafts', ['payload' => ['description' => 'محدّث']] + $body, false)->assertOk()->json('data');
        $this->assertSame($d1['id'], $d2['id']); // upsert by (user, form_type, form_key)
        $this->assertSame('محدّث', $d2['payload']['description']);
        $this->send($this->manager, 'PUT', '/api/v1/drafts', $body + ['form_key' => 'r5'], false)->assertOk();
        $this->assertSame(2, Draft::count());
        $this->send($this->manager, 'PUT', '/api/v1/drafts', ['form_type' => 'bogus', 'payload' => ['a' => 1]], false)->assertStatus(422);
        $this->send($this->manager, 'PUT', '/api/v1/drafts', ['form_type' => 'note'], false)->assertStatus(422);

        $this->assertCount(2, $this->read($this->manager, '/api/v1/drafts')->json('data'));
        $this->assertCount(1, $this->read($this->manager, '/api/v1/drafts?form_type=request_create&per_page=1')->json('data'));
        $this->assertCount(0, $this->read($this->secretary, '/api/v1/drafts')->json('data'));
        $this->assertCount(0, $this->read($this->secretary, '/api/v1/drafts?user_id='.$this->manager->id)->json('data')); // user comes from token only

        $this->send($this->secretary, 'DELETE', "/api/v1/drafts/{$d1['id']}", [], false)->assertStatus(404);
        $this->send($this->manager, 'DELETE', "/api/v1/drafts/{$d1['id']}", [], false)->assertStatus(204);
        $this->assertDatabaseMissing('drafts', ['id' => $d1['id']]);
        $this->send($this->manager, 'DELETE', "/api/v1/drafts/{$d1['id']}", [], false)->assertStatus(404);
    }

    public function test_notifications_recipient_only(): void
    {
        $this->newRequest();
        $mine = Notification::where('recipient_id', $this->secretary->id)->first();
        $this->assertNotNull($mine);
        $this->assertCount(1, $this->read($this->secretary, '/api/v1/notifications')->json('data'));
        $this->assertCount(0, $this->read($this->engineer, '/api/v1/notifications')->json('data'));
        $this->act($this->engineer, "/api/v1/notifications/{$mine->id}/read")->assertStatus(404);
        $this->assertNull($mine->fresh()->read_at);
        $this->act($this->secretary, "/api/v1/notifications/{$mine->id}/read")->assertOk();
        $this->act($this->secretary, "/api/v1/notifications/{$mine->id}/read")->assertOk(); // naturally idempotent
        $this->assertNotNull($mine->fresh()->read_at);
        $this->assertCount(0, $this->read($this->secretary, '/api/v1/notifications?read=false')->json('data'));
        $this->assertCount(1, $this->read($this->secretary, '/api/v1/notifications?read=true')->json('data'));
        $this->read($this->secretary, '/api/v1/notifications?read=maybe')->assertStatus(422);
    }

    public function test_notification_channels_are_internal_only(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        \Illuminate\Support\Facades\Notification::fake();
        $this->requestAt('closed');
        \Illuminate\Support\Facades\Mail::assertNothingSent();
        \Illuminate\Support\Facades\Notification::assertNothingSent();
    }

    public function test_read_only_lookups_for_forms(): void
    {
        foreach ([$this->manager, $this->secretary, $this->engineer, $this->technician] as $u) {
            $this->read($u, '/api/v1/reference/intake-channels')->assertOk();
            $this->read($u, '/api/v1/reference/request-types')->assertOk();
            $this->read($u, '/api/v1/reference/task-types')->assertOk();
        }
        $this->act($this->secretary, '/api/v1/reference/intake-channels', ['name' => 'x'])->assertStatus(403); // writes stay chairman-only
        $sites = $this->read($this->manager, '/api/v1/sites')->assertOk()->json('data');
        $this->assertSame([$this->site->id], array_column($sites, 'id'));
        $this->read($this->manager, '/api/v1/sites/'.\App\Models\Site::factory()->create()->id)->assertStatus(404);
    }
}
