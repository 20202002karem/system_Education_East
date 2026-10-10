<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\M3Responses;
use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

/** M3-API-035, 036 — recipient only. */
class NotificationController extends Controller
{
    use M3Responses;

    public function index(Request $request)
    {
        $request->validate(['read' => ['nullable', 'in:true,false,1,0']]);
        $query = Notification::where('recipient_id', $request->user()->id)->orderByDesc('created_at')->orderByDesc('id');
        if ($request->filled('read')) {
            filter_var($request->query('read'), FILTER_VALIDATE_BOOLEAN) ? $query->whereNotNull('read_at') : $query->whereNull('read_at');
        }

        return $this->paginated($request, $query, fn ($rows) => NotificationResource::collection($rows)->resolve());
    }

    public function read(Request $request, int $id)
    {
        $n = Notification::where('recipient_id', $request->user()->id)->whereKey($id)->first() ?? abort(404);
        if ($n->read_at === null) {
            $n->update(['read_at' => now('UTC')]);
        }

        return ApiResponse::ok(NotificationResource::make($n)->resolve());
    }
}
