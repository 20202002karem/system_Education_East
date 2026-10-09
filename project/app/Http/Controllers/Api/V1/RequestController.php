<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\M3Responses;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNoteRequest;
use App\Http\Requests\StoreProposalRequest;
use App\Http\Requests\StoreServiceRequestRequest;
use App\Http\Resources\AssignmentResource;
use App\Http\Resources\CycleResource;
use App\Http\Resources\NoteResource;
use App\Http\Resources\ProposalResource;
use App\Http\Resources\ServiceRequestResource;
use App\Models\Asset;
use App\Models\IntakeChannel;
use App\Models\RequestAssignment;
use App\Models\RequestNote;
use App\Models\ServiceRequest;
use App\Models\Site;
use App\Models\User;
use App\Services\RequestWorkflowService;
use App\Support\ApiResponse;
use App\Support\RequestAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** M3 Requests — M3-API-001, 002, 003, 012, 013, 014, 015, 016. */
class RequestController extends Controller
{
    use M3Responses;

    public function __construct(protected RequestWorkflowService $service) {}

    public function index(Request $request)
    {
        $request->validate([
            'status' => ['nullable', Rule::in(\App\Models\RequestCycle::STATUSES)],
            'priority' => ['nullable', Rule::in(ServiceRequest::PRIORITIES)],
            'origin_site_id' => ['nullable', 'integer'],
            'assignee_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', Rule::in(['created_at', '-created_at', 'priority', '-priority'])],
        ]);
        $query = RequestAccess::scopeRequests(ServiceRequest::query(), $request->user())->with(['currentCycle', 'originSite']);

        $cycleFilter = function ($q, array $where) {
            $q->whereExists(function ($s) use ($where) {
                $s->selectRaw('1')->from('request_cycles as rc')
                    ->whereColumn('rc.request_id', 'requests.id')->whereColumn('rc.cycle_no', 'requests.current_cycle_no');
                foreach ($where as $col => $val) {
                    $s->where("rc.$col", $val);
                }
            });
        };
        if ($request->filled('status')) {
            $cycleFilter($query, ['status' => $request->query('status')]);
        }
        if ($request->filled('assignee_id')) {
            $cycleFilter($query, ['assignee_user_id' => (int) $request->query('assignee_id')]);
        }
        if ($request->filled('origin_site_id')) {
            $query->where('requests.origin_site_id', (int) $request->query('origin_site_id'));
        }
        if ($request->filled('priority')) {
            $query->where('requests.priority', $request->query('priority'));
        }
        if ($request->filled('q')) {
            $like = $this->like((string) $request->query('q'));
            $query->where(fn ($w) => $w->where('requests.ref_no', 'like', $like)->orWhere('requests.description', 'like', $like));
        }
        $sort = $request->query('sort', '-created_at');
        $col = ltrim($sort, '-');
        if ($col === 'priority') {
            $query->orderByRaw("CASE requests.priority WHEN 'emergency' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 WHEN 'low' THEN 4 ELSE 5 END ".(str_starts_with($sort, '-') ? 'DESC' : 'ASC'));
        } else {
            $query->orderBy("requests.$col", str_starts_with($sort, '-') ? 'desc' : 'asc');
        }
        $query->orderByDesc('requests.id');

        return $this->paginated($request, $query, fn ($rows) => ServiceRequestResource::collection($rows)->resolve());
    }

    public function show(Request $request, int $id)
    {
        $r = $this->visible($request, $id)->load(['currentCycle.assignee', 'originSite', 'cancellation']);

        return ApiResponse::ok(ServiceRequestResource::make($r)->resolve());
    }

    public function store(StoreServiceRequestRequest $request)
    {
        $actor = $request->user();

        return $this->idempotent($request, function () use ($request, $actor) {
            $d = $request->validated();
            $site = Site::find($d['origin_site_id']) ?? abort(404);
            if ($actor->role === 'school_manager' && ! in_array($site->id, RequestAccess::siteIds($actor) ?? [], true)) {
                abort(403);
            }
            if ($site->status !== 'active') {
                return ApiResponse::error('validation_failed', 'الموقع غير نشط', 422, ['origin_site_id' => ['الموقع غير نشط']]);
            }
            $channel = IntakeChannel::find($d['channel_id']) ?? abort(404);
            if (! $channel->is_active) {
                return ApiResponse::error('validation_failed', 'القناة غير نشطة', 422, ['channel_id' => ['القناة غير نشطة']]);
            }
            if (! empty($d['asset_id'])) {
                $asset = Asset::find($d['asset_id']) ?? abort(404);
                if ((int) $asset->current_site_id !== (int) $site->id) {
                    return ApiResponse::error('validation_failed', 'الجهاز لا يتبع موقع الطلب', 422, ['asset_id' => ['الجهاز لا يتبع الموقع']]);
                }
            }
            if ($actor->role === 'secretary' && empty($d['requester_name'])) {
                return ApiResponse::error('validation_failed', 'اسم مقدّم الطلب مطلوب عند التسجيل نيابةً', 422, ['requester_name' => ['مطلوب']]);
            }
            $r = $this->service->create($actor, $d, $request->ip())->load(['currentCycle', 'originSite']);

            return ApiResponse::created(ServiceRequestResource::make($r)->resolve());
        });
    }

    public function cycles(Request $request, int $id)
    {
        $r = $this->visible($request, $id);

        return $this->paginated($request, $r->cycles()->with('assignee')->orderBy('cycle_no'), fn ($rows) => CycleResource::collection($rows)->resolve());
    }

    public function assignments(Request $request, int $id)
    {
        $request->validate(['cycle_id' => ['nullable', 'integer']]);
        $r = $this->visible($request, $id);
        $query = RequestAssignment::query()->whereIn('cycle_id', $r->cycles()->select('id'))->orderBy('from')->orderBy('id');
        if ($request->filled('cycle_id')) {
            $query->where('cycle_id', (int) $request->query('cycle_id'));
        }

        return $this->paginated($request, $query, fn ($rows) => AssignmentResource::collection($rows)->resolve());
    }

    public function notes(Request $request, int $id)
    {
        $r = $this->visible($request, $id);
        $query = RequestNote::query()->with('author:id,name')->whereIn('cycle_id', $r->cycles()->select('id'))->orderBy('created_at')->orderBy('id');
        if ($request->user()->role === 'school_manager') {
            $query->where('visibility', 'external'); // BR-M3-05, enforced server-side
        }

        return $this->paginated($request, $query, fn ($rows) => NoteResource::collection($rows)->resolve());
    }

    public function storeNote(StoreNoteRequest $request, int $id)
    {
        $r = $this->visible($request, $id);
        $d = $request->validated();

        return $this->idempotent($request, function () use ($request, $r, $d) {
            $note = $this->service->addNote($request->user(), $r, $d['visibility'], $d['body']);

            return ApiResponse::created(NoteResource::make($note)->resolve());
        });
    }

    public function propose(StoreProposalRequest $request, int $id)
    {
        $r = $this->visible($request, $id);

        return $this->idempotent($request, fn () => ApiResponse::created(
            ProposalResource::make($this->service->propose($request->user(), $r, $request->validated(), $request->ip()))->resolve()
        ));
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
