<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\M3Responses;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttachmentRequest;
use App\Http\Resources\AttachmentResource;
use App\Models\Attachment;
use App\Services\AttachmentService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/** M3-API-030, 031, 037 (+ the signed file route that M3-API-031 hands out). No delete, no replace. */
class AttachmentController extends Controller
{
    use M3Responses;

    public function __construct(protected AttachmentService $service) {}

    public function index(Request $request)
    {
        $request->validate([
            'owner_type' => ['required', 'in:request,request_note,task'],
            'owner_id' => ['required', 'integer'],
        ]);
        $this->service->assertParentVisible($request->user(), $request->query('owner_type'), (int) $request->query('owner_id'));
        $query = Attachment::where('owner_type', $request->query('owner_type'))->where('owner_id', (int) $request->query('owner_id'))->orderBy('created_at')->orderBy('id');

        return $this->paginated($request, $query, fn ($rows) => AttachmentResource::collection($rows)->resolve());
    }

    public function store(StoreAttachmentRequest $request)
    {
        $d = $request->validated();

        return $this->idempotent($request, fn () => ApiResponse::created(AttachmentResource::make(
            $this->service->store($request->user(), $d['owner_type'], (int) $d['owner_id'], $request->file('file'), $request->ip()))->resolve()));
    }

    public function download(Request $request, int $id)
    {
        $a = Attachment::find($id) ?? abort(404);
        $this->service->assertParentVisible($request->user(), $a->owner_type, $a->owner_id);
        $expires = now()->addMinutes(5);

        return ApiResponse::ok([
            'url' => URL::temporarySignedRoute('attachments.file', $expires, ['attachment' => $a->id]),
            'expires_at' => $expires->copy()->utc()->toIso8601ZuluString(),
        ]);
    }

    /** Public by design: the temporary signature IS the credential (scope was checked when it was issued). */
    public function file(Request $request, int $attachment)
    {
        if (! $request->hasValidSignature()) {
            return ApiResponse::error('forbidden', 'الرابط غير صالح أو منتهي', 403);
        }
        $a = Attachment::find($attachment) ?? abort(404);
        $disk = Storage::disk('local');
        if (! $disk->exists($a->storage_path)) {
            abort(404);
        }

        return $disk->download($a->storage_path, $a->original_filename, [
            'Content-Type' => 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
