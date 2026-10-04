<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReferenceDataRequest;
use App\Models\DeviceCategory;
use App\Models\IntakeChannel;
use App\Models\RequestType;
use App\Models\TaskType;
use App\Services\AuditLogger;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Batch 3 §2.4 — "نفس النمط يتكرر" for device-categories, request-types,
 * task-types, intake-channels: same actions, same authorization, same
 * constraints, one controller parameterized by resource key to avoid
 * duplicating four near-identical controllers.
 */
class ReferenceDataController extends Controller
{
    protected array $map = [
        'device-categories' => DeviceCategory::class,
        'request-types' => RequestType::class,
        'task-types' => TaskType::class,
        'intake-channels' => IntakeChannel::class,
    ];

    public function __construct(protected AuditLogger $audit) {}

    protected function modelClass(string $resource): string
    {
        return $this->map[$resource] ?? abort(404);
    }

    public function index(string $resource)
    {
        $class = $this->modelClass($resource);

        return ApiResponse::ok($class::all());
    }

    public function store(ReferenceDataRequest $request, string $resource)
    {
        $class = $this->modelClass($resource);
        $actor = $request->user();

        $fields = ['name'];
        if ($resource === 'task-types') {
            $fields = ['name', 'is_administrative', 'secretary_assignable'];
        } elseif ($resource !== 'device-categories') {
            $fields = ['name', 'is_active'];
        }

        /** @var Model $item */
        $item = $class::create($request->only($fields));

        $this->audit->record($actor->id, 'reference.updated', $resource, $item->id, after: $item->only($fields));

        return ApiResponse::created($item);
    }

    public function update(ReferenceDataRequest $request, string $resource, int $id)
    {
        $class = $this->modelClass($resource);
        $actor = $request->user();
        $item = $class::findOrFail($id);

        $fields = ['name'];
        if ($resource === 'task-types') {
            $fields = ['name', 'is_administrative', 'secretary_assignable'];
        } elseif ($resource !== 'device-categories') {
            $fields = ['name', 'is_active'];
        }

        $before = $item->only($fields);
        $item->fill($request->only($fields));
        $item->save();

        $this->audit->record($actor->id, 'reference.updated', $resource, $item->id, before: $before, after: $item->only($fields));

        return ApiResponse::ok($item);
    }
}
