<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\M3Responses;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActionVersionRequest;
use App\Http\Requests\AssignRequest;
use App\Http\Requests\CancelTaskRequest;
use App\Http\Requests\CompleteTaskRequest;
use App\Http\Requests\StoreProposalRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\HoldRequest;
use App\Http\Resources\ProposalResource;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Services\RequestWorkflowService;
use App\Services\TaskWorkflowService;
use App\Support\ApiResponse;
use App\Support\RequestAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** M3 Tasks — M3-API-017..026. */
class TaskController extends Controller
{
    use M3Responses;

    public function __construct(protected TaskWorkflowService $service, protected RequestWorkflowService $requests) {}

    public function index(Request $request)
    {
        $request->validate([
            'status' => ['nullable', Rule::in(Task::STATUSES)],
            'assignee_id' => ['nullable', 'integer'],
            'request_cycle_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', Rule::in(['due_at', '-due_at', 'created_at', '-created_at'])],
        ]);
        $query = RequestAccess::scopeTasks(Task::query(), $request->user());
        foreach (['status', 'assignee_id', 'request_cycle_id'] as $f) {
            if ($request->filled($f)) {
                $query->where("tasks.$f", $request->query($f));
            }
        }
        if ($request->filled('q')) {
            $query->where('tasks.ref_no', 'like', $this->like((string) $request->query('q')));
        }
        $sort = $request->query('sort', '-created_at');
        $query->orderBy('tasks.'.ltrim($sort, '-'), str_starts_with($sort, '-') ? 'desc' : 'asc')->orderByDesc('tasks.id');

        return $this->paginated($request, $query, fn ($rows) => TaskResource::collection($rows)->resolve());
    }

    public function show(Request $request, int $id)
    {
        return ApiResponse::ok(TaskResource::make($this->visible($request, $id))->resolve());
    }

    public function store(StoreTaskRequest $request)
    {
        return $this->idempotent($request, fn () => ApiResponse::created(
            TaskResource::make($this->service->create($request->user(), $request->validated(), $request->ip()))->resolve()
        ));
    }

    public function assign(AssignRequest $request, int $id)
    {
        $t = $this->visible($request, $id);
        $d = $request->validated();

        return $this->idempotent($request, fn () => ApiResponse::ok(TaskResource::make(
            $this->service->assign($request->user(), $t, $d['assignee_id'], $d['reason'] ?? null, $d['version'] ?? null, $request->ip())
        )->resolve()));
    }

    public function start(ActionVersionRequest $request, int $id)
    {
        $t = $this->visible($request, $id);

        return $this->idempotent($request, fn () => ApiResponse::ok(TaskResource::make(
            $this->service->start($request->user(), $t, $request->validated()['version'] ?? null, $request->ip()))->resolve()));
    }

    public function hold(HoldRequest $request, int $id)
    {
        $t = $this->visible($request, $id);

        return $this->idempotent($request, fn () => ApiResponse::ok(TaskResource::make(
            $this->service->hold($request->user(), $t, $request->validated()['version'] ?? null, $request->ip()))->resolve()));
    }

    public function resume(ActionVersionRequest $request, int $id)
    {
        $t = $this->visible($request, $id);

        return $this->idempotent($request, fn () => ApiResponse::ok(TaskResource::make(
            $this->service->resume($request->user(), $t, $request->validated()['version'] ?? null, $request->ip()))->resolve()));
    }

    public function complete(CompleteTaskRequest $request, int $id)
    {
        $t = $this->visible($request, $id);
        $d = $request->validated();

        return $this->idempotent($request, fn () => ApiResponse::ok(TaskResource::make(
            $this->service->complete($request->user(), $t, $d['result_summary'], $d['version'] ?? null, $request->ip()))->resolve()));
    }

    public function cancel(CancelTaskRequest $request, int $id)
    {
        $t = $this->visible($request, $id);
        $d = $request->validated();

        return $this->idempotent($request, fn () => ApiResponse::ok(TaskResource::make(
            $this->service->cancel($request->user(), $t, $d, $d['version'] ?? null, $request->ip()))->resolve()));
    }

    public function propose(StoreProposalRequest $request, int $id)
    {
        $t = $this->visible($request, $id);

        return $this->idempotent($request, fn () => ApiResponse::created(ProposalResource::make(
            $this->service->propose($request->user(), $t, $request->validated(), $this->requests, $request->ip()))->resolve()));
    }

    protected function visible(Request $request, int $id): Task
    {
        $t = Task::find($id);
        if (! $t || ! RequestAccess::canSeeTask($request->user(), $t)) {
            abort(404);
        }

        return $t;
    }
}
