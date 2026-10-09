<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\M3Responses;
use App\Http\Controllers\Controller;
use App\Http\Requests\PutDraftRequest;
use App\Http\Resources\DraftResource;
use App\Models\Draft;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

/** M3-API-032..034. The owner always comes from the token, never from query/body (IDOR). */
class DraftController extends Controller
{
    use M3Responses;

    public function put(PutDraftRequest $request)
    {
        $d = $request->validated();
        $draft = Draft::firstOrNew(['user_id' => $request->user()->id, 'form_type' => $d['form_type'], 'form_key' => $d['form_key'] ?? null]);
        $draft->payload = $d['payload'];
        $draft->updated_at = now('UTC');
        $draft->save();

        return ApiResponse::ok(DraftResource::make($draft)->resolve());
    }

    public function index(Request $request)
    {
        $request->validate(['form_type' => ['nullable', 'in:request_create,note,closure,cancellation,proposal']]);
        $query = Draft::where('user_id', $request->user()->id)->orderByDesc('updated_at')->orderByDesc('id');
        if ($request->filled('form_type')) {
            $query->where('form_type', $request->query('form_type'));
        }

        return $this->paginated($request, $query, fn ($rows) => DraftResource::collection($rows)->resolve());
    }

    public function destroy(Request $request, int $id)
    {
        $draft = Draft::where('user_id', $request->user()->id)->whereKey($id)->first() ?? abort(404);
        $draft->delete(); // the only physical delete in the system (DBD-M3-04)

        return ApiResponse::noContent();
    }
}
