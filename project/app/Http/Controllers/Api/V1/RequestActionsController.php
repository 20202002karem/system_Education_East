<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\M3Responses;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActionVersionRequest;
use App\Http\Requests\AssignRequest;
use App\Http\Requests\CancelServiceRequestRequest;
use App\Http\Requests\CloseServiceRequestRequest;
use App\Http\Requests\HoldRequest;
use App\Http\Requests\ReopenServiceRequestRequest;
use App\Http\Requests\TriageServiceRequestRequest;
use App\Http\Resources\AssignmentResource;
use App\Http\Resources\ServiceRequestResource;
use App\Models\RequestType;
use App\Models\ServiceRequest;
use App\Services\RequestWorkflowService;
use App\Support\ApiResponse;
use App\Support\RequestAccess;
use Illuminate\Http\Request;

/** M3 request state transitions — M3-API-004..011 (no free PATCH, DD-M3-02). */
class RequestActionsController extends Controller
{
    use M3Responses;

    public function __construct(protected RequestWorkflowService $service) {}

    public function triage(TriageServiceRequestRequest $request, int $id)
    {
        $r = $this->visible($request, $id);
        $d = $request->validated();
        $type = RequestType::find($d['request_type_id']) ?? abort(404);
        if (! $type->is_active) {
            return ApiResponse::error('validation_failed', 'نوع الطلب غير نشط', 422, ['request_type_id' => ['نوع غير نشط']]);
        }

        return $this->idempotent($request, function () use ($request, $r, $d) {
            $this->service->triage($request->user(), $r, $d['request_type_id'], $d['priority'], $d['version'] ?? null, $request->ip());

            return $this->respond($r);
        });
    }

    public function assign(AssignRequest $request, int $id)
    {
        $r = $this->visible($request, $id);
        $d = $request->validated();

        return $this->idempotent($request, function () use ($request, $r, $d) {
            [, $assignment] = $this->service->assign($request->user(), $r, $d['assignee_id'], $d['reason'] ?? null, $d['version'] ?? null, $request->ip());

            return $this->respond($r, 200, ['assignment' => AssignmentResource::make($assignment)->resolve()]);
        });
    }

    public function start(ActionVersionRequest $request, int $id)
    {
        $r = $this->visible($request, $id);

        return $this->idempotent($request, function () use ($request, $r) {
            $this->service->start($request->user(), $r, $request->validated()['version'] ?? null, $request->ip());

            return $this->respond($r);
        });
    }

    public function hold(HoldRequest $request, int $id)
    {
        $r = $this->visible($request, $id);
        $d = $request->validated();

        return $this->idempotent($request, function () use ($request, $r, $d) {
            $this->service->hold($request->user(), $r, $d['hold_reason'] ?? null, $d['version'] ?? null, $request->ip());

            return $this->respond($r);
        });
    }

    public function resume(ActionVersionRequest $request, int $id)
    {
        $r = $this->visible($request, $id);

        return $this->idempotent($request, function () use ($request, $r) {
            $this->service->resume($request->user(), $r, $request->validated()['version'] ?? null, $request->ip());

            return $this->respond($r);
        });
    }

    public function close(CloseServiceRequestRequest $request, int $id)
    {
        $r = $this->visible($request, $id);
        $d = $request->validated();

        return $this->idempotent($request, function () use ($request, $r, $d) {
            $this->service->close($request->user(), $r, $d, $d['version'] ?? null, $request->ip());

            return $this->respond($r);
        });
    }

    public function cancel(CancelServiceRequestRequest $request, int $id)
    {
        $r = $this->visible($request, $id);
        $d = $request->validated();

        return $this->idempotent($request, function () use ($request, $r, $d) {
            $this->service->cancel($request->user(), $r, $d, $d['version'] ?? null, $request->ip());

            return $this->respond($r);
        });
    }

    public function reopen(ReopenServiceRequestRequest $request, int $id)
    {
        $r = $this->visible($request, $id);

        return $this->idempotent($request, function () use ($request, $r) {
            $this->service->reopen($request->user(), $r, $request->validated()['reason'], $request->ip());

            return $this->respond($r->refresh(), 201);
        });
    }

    protected function respond(ServiceRequest $r, int $status = 200, array $extra = [])
    {
        $r = ServiceRequest::with(['currentCycle.assignee', 'originSite', 'cancellation'])->findOrFail($r->id);

        return ApiResponse::ok(ServiceRequestResource::make($r)->resolve() + $extra, $status);
    }

    protected function visible(Request $request, int $id): ServiceRequest
    {
        $r = ServiceRequest::find($id);
        if (! $r || ! RequestAccess::canSeeRequest($request->user(), $r)) {
            abort(404);
        }

        return $r;
    }
}
