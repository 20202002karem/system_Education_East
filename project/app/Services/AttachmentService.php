<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\RequestCycle;
use App\Models\RequestNote;
use App\Models\ServiceRequest;
use App\Models\Task;
use App\Models\User;
use App\Support\RequestAccess;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** IN-20 / Batch 1 §17: private storage, SHA-256, no replace, no delete. Limits (D-34) intentionally not enforced. */
class AttachmentService
{
    public function __construct(protected AuditLogger $audit) {}

    /** Resolves the parent entity inside the actor's scope or aborts 404. Returns the owning request note when relevant. */
    public function assertParentVisible(User $actor, string $ownerType, int $ownerId): void
    {
        match ($ownerType) {
            'request' => $this->requestVisible($actor, ServiceRequest::find($ownerId)),
            'task' => $this->taskVisible($actor, Task::find($ownerId)),
            'request_note' => $this->noteVisible($actor, RequestNote::find($ownerId)),
        };
    }

    protected function requestVisible(User $actor, ?ServiceRequest $r): void
    {
        if (! $r || ! RequestAccess::canSeeRequest($actor, $r)) {
            abort(404);
        }
    }

    protected function taskVisible(User $actor, ?Task $t): void
    {
        if (! $t || ! RequestAccess::canSeeTask($actor, $t)) {
            abort(404);
        }
    }

    protected function noteVisible(User $actor, ?RequestNote $n): void
    {
        if (! $n) {
            abort(404);
        }
        $this->requestVisible($actor, RequestCycle::find($n->cycle_id)?->request);
        if ($n->visibility === 'internal' && $actor->role === 'school_manager') {
            abort(404); // attachments inherit the sensitivity of an internal note (BR-M3-05)
        }
    }

    public function store(User $actor, string $ownerType, int $ownerId, UploadedFile $file, ?string $ip): Attachment
    {
        $this->assertParentVisible($actor, $ownerType, $ownerId);
        if ($ownerType === 'request_note' && RequestNote::find($ownerId)->author_id !== $actor->id) {
            abort(403); // only the note author may attach to it
        }
        $path = Storage::disk('local')->putFile('attachments/'.now('UTC')->format('Y/m'), $file);
        $attachment = Attachment::create([
            'owner_type' => $ownerType, 'owner_id' => $ownerId, 'uploaded_by' => $actor->id, 'storage_path' => $path,
            'sha256' => hash_file('sha256', $file->getRealPath()), 'size_bytes' => $file->getSize(),
            'mime_type' => substr((string) ($file->getMimeType() ?: 'application/octet-stream'), 0, 100),
            'original_filename' => Str::limit(basename($file->getClientOriginalName()), 250, ''),
        ]);
        $this->audit->record($actor->id, 'attachment.uploaded', 'attachment', $attachment->id, null,
            ['owner_type' => $ownerType, 'owner_id' => $ownerId, 'sha256' => $attachment->sha256, 'size_bytes' => $attachment->size_bytes], null, [], 'web', $ip);

        return $attachment;
    }
}
