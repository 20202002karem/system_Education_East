<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSiteRequest;
use App\Http\Requests\UpdateSiteRequest;
use App\Models\Site;
use App\Services\AuditLogger;
use App\Services\IdempotencyService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

/**
 * Batch 3 §2.3 — Sites. Read AND write are chairman-only in M1 (§4: read for
 * other roles is "⏳ يحتاج قراراً", closed by default). Do not relax this
 * without an explicit product decision — see App\Support\AccessPolicy::canReadSites().
 */
class SiteController extends Controller
{
    public function __construct(
        protected AuditLogger $audit,
        protected IdempotencyService $idempotency,
    ) {}

    public function index(Request $request)
    {
        $perPage = min((int) $request->query('per_page', 20), 100);
        $query = Site::query();
        if (! $request->user()->isChairman()) {
            // M3 BASELINE CHANGE (read-only): other roles see only active sites inside their scope, minimal fields.
            $ids = \App\Support\RequestAccess::siteIds($request->user());
            $query->where('status', 'active');
            if ($ids !== null) {
                $query->whereIn('id', $ids);
            }
        }

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $paginator = $query->orderBy('id')->paginate($perPage, ['*'], 'page', (int) $request->query('page', 1));

        $items = $request->user()->isChairman()
            ? $paginator->items()
            : collect($paginator->items())->map(fn ($s) => $s->only(['id', 'type', 'code', 'name_ar', 'status']))->all();

        return ApiResponse::ok(
            $items, 200,
            ['page' => $paginator->currentPage(), 'per_page' => $perPage, 'total' => $paginator->total()]
        );
    }

    public function store(StoreSiteRequest $request)
    {
        $idKey = $request->header('Idempotency-Key');
        $actor = $request->user();

        if ($cached = $this->idempotency->find($actor->id, $idKey)) {
            return response()->json($cached->response_snapshot, $cached->response_status);
        }

        $site = Site::create($request->only(['type', 'code', 'name_ar']));

        $this->audit->record($actor->id, 'site.created', 'site', $site->id, after: $site->only(['type', 'code', 'name_ar']));

        $response = ApiResponse::created($site);
        $this->idempotency->remember($actor->id, $idKey, $response);

        return $response;
    }

    public function show(Request $request, Site $site)
    {
        $user = $request->user();
        if (! $user->isChairman()) {
            $ids = \App\Support\RequestAccess::siteIds($user);
            if ($site->status !== 'active' || ($ids !== null && ! in_array($site->id, $ids, true))) {
                abort(404);
            }

            return ApiResponse::ok($site->only(['id', 'type', 'code', 'name_ar', 'status']));
        }

        return ApiResponse::ok($site);
    }

    public function update(UpdateSiteRequest $request, Site $site)
    {
        $actor = $request->user();
        $before = $site->only(['type', 'code', 'name_ar']);

        $site->fill($request->only(['type', 'code', 'name_ar']));
        $site->save();

        $this->audit->record($actor->id, 'site.updated', 'site', $site->id, before: $before, after: $site->only(['type', 'code', 'name_ar']));

        return ApiResponse::ok($site);
    }

    public function archive(Request $request, Site $site)
    {
        $actor = $request->user();
        $site->update(['status' => 'archived']);

        $this->audit->record($actor->id, 'site.archived', 'site', $site->id);

        return ApiResponse::ok($site);
    }
}
