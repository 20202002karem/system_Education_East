<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\DomainConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeAssetStatusRequest;
use App\Http\Requests\CorrectAssetIdentifierRequest;
use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Http\Resources\AssetHistoryResource;
use App\Http\Resources\AssetResource;
use App\Models\Asset;
use App\Models\DeviceCategory;
use App\Models\Site;
use App\Services\AssetService;
use App\Services\IdempotencyService;
use App\Support\ApiResponse;
use App\Support\AssetAccess;
use App\Support\AssetIdentifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** M2 — Assets (Batch 3, API-AST-01..09). */
class AssetController extends Controller
{
    public function __construct(protected AssetService $service, protected IdempotencyService $idempotency) {}

    public function index(Request $request)
    {
        $request->validate([
            'status' => ['nullable', Rule::in(Asset::MANUAL_STATUSES)],
            'category_id' => ['nullable', 'integer'],
            'site_id' => ['nullable', 'integer'],
            'sort' => ['nullable', Rule::in(['inventory_no', '-inventory_no', 'created_at', '-created_at'])],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $user = $request->user();
        $query = AssetAccess::scopeVisible(Asset::query(), $user);

        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->query('category_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }
        if ($request->filled('site_id')) {
            $query->where('current_site_id', (int) $request->query('site_id')); // AND with actor scope
        }
        if ($request->filled('q')) {
            $term = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], AssetIdentifier::normalize($request->query('q')) ?? '');
            $query->where(fn ($w) => $w->where('inventory_no', 'like', "%{$term}%")->orWhere('serial_no', 'like', "%{$term}%"));
        }
        $sort = $request->query('sort', 'inventory_no');
        $query->orderBy(ltrim($sort, '-'), str_starts_with($sort, '-') ? 'desc' : 'asc')->orderBy('id');

        return $this->paginated($request, $query, fn ($rows) => AssetResource::collection($rows)->resolve());
    }

    public function show(Request $request, Asset $asset)
    {
        $this->ensureVisible($request, $asset);

        return ApiResponse::ok(AssetResource::make($asset)->resolve());
    }

    public function store(StoreAssetRequest $request)
    {
        $actor = $request->user();
        $key = $request->header('Idempotency-Key');
        if ($cached = $this->idempotency->find($actor->id, $key)) {
            return response()->json($cached->response_snapshot, $cached->response_status);
        }

        $data = $request->validated();
        $this->assertDistinct($data['legacy_numbers'] ?? []);
        $category = DeviceCategory::find($data['category_id']) ?? abort(404);
        $site = Site::find($data['current_site_id']) ?? abort(404);
        if (! AssetAccess::canSeeSite($actor, $site->id)) {
            abort(403);
        }
        if ($site->status !== 'active') {
            return ApiResponse::error('validation_failed', 'الموقع غير نشط', 422, ['current_site_id' => ['الموقع غير نشط']]);
        }
        $this->guardSerial($data['serial_no'] ?? null);

        return $this->run(function () use ($actor, $data, $request, $key) {
            $asset = $this->service->create($actor, $data, $request->ip());

            return $this->remember($actor->id, $key, ApiResponse::created(AssetResource::make($asset)->resolve()));
        });
    }

    public function update(UpdateAssetRequest $request, Asset $asset)
    {
        $data = $request->validated();
        if (isset($data['category_id'])) {
            DeviceCategory::find($data['category_id']) ?? abort(404);
        }

        return $this->run(function () use ($request, $asset, $data) {
            $asset = $this->service->update($request->user(), $asset, $data, $request->ip());

            return ApiResponse::ok(AssetResource::make($asset)->resolve());
        });
    }

    public function changeStatus(ChangeAssetStatusRequest $request, Asset $asset)
    {
        $actor = $request->user();
        $key = $request->header('Idempotency-Key');
        if ($cached = $this->idempotency->find($actor->id, $key)) {
            return response()->json($cached->response_snapshot, $cached->response_status);
        }
        $d = $request->validated();

        return $this->run(function () use ($request, $actor, $asset, $d, $key) {
            [$asset, $entry] = $this->service->changeStatus($actor, $asset, $d['to_status'], $d['reason'] ?? null, (int) $d['version'], $request->ip());

            return $this->remember($actor->id, $key, ApiResponse::created([
                'asset' => ['id' => $asset->id, 'status' => $asset->status, 'version' => $asset->version],
                'status_history_entry' => AssetHistoryResource::make($entry)->resolve(),
            ]));
        });
    }

    public function correctIdentifier(CorrectAssetIdentifierRequest $request, Asset $asset)
    {
        $actor = $request->user();
        $key = $request->header('Idempotency-Key');
        if ($cached = $this->idempotency->find($actor->id, $key)) {
            return response()->json($cached->response_snapshot, $cached->response_status);
        }
        $d = $request->validated();
        if ($d['field_name'] === 'serial_no') {
            $this->guardSerial($d['new_value']);
        } elseif (AssetIdentifier::normalize($d['new_value']) === null) {
            return ApiResponse::error('validation_failed', 'القيمة غير صالحة', 422, ['new_value' => ['القيمة غير صالحة']]);
        }

        return $this->run(function () use ($request, $actor, $asset, $d, $key) {
            [$asset, $c] = $this->service->correctIdentifier($actor, $asset, $d['field_name'], $d['new_value'], $d['reason'], $request->ip());
            $asset->refresh();

            return $this->remember($actor->id, $key, ApiResponse::created([
                'asset' => ['id' => $asset->id, $d['field_name'] => $asset->{$d['field_name']}],
                'correction' => AssetHistoryResource::make($c)->resolve(),
            ]));
        });
    }

    public function statusHistory(Request $request, Asset $asset)
    {
        $this->ensureVisible($request, $asset);

        return $this->history($request, $asset->statusHistory()->orderByDesc('changed_at')->orderByDesc('id'));
    }

    public function identifierCorrections(Request $request, Asset $asset)
    {
        $this->ensureVisible($request, $asset);

        return $this->history($request, $asset->identifierCorrections()->orderByDesc('corrected_at')->orderByDesc('id'));
    }

    public function legacyNumbers(Request $request, Asset $asset)
    {
        $this->ensureVisible($request, $asset);

        return $this->history($request, $asset->legacyNumbers()->orderBy('id'));
    }

    // ---- helpers ----

    protected function history(Request $request, $query)
    {
        return $this->paginated($request, $query, fn ($rows) => AssetHistoryResource::collection($rows)->resolve());
    }

    protected function paginated(Request $request, $query, callable $map)
    {
        $perPage = max(1, min((int) $request->query('per_page', 20), 100));
        $p = $query->paginate($perPage, ['*'], 'page', max(1, (int) $request->query('page', 1)));

        return ApiResponse::ok($map(collect($p->items())), 200, ['page' => $p->currentPage(), 'per_page' => $perPage, 'total' => $p->total()]);
    }

    protected function ensureVisible(Request $request, Asset $asset): void
    {
        if (! AssetAccess::canSee($request->user(), $asset)) {
            abort(404);
        }
    }

    protected function assertDistinct(array $numbers): void
    {
        $norm = array_map(fn ($n) => AssetIdentifier::normalize($n), $numbers);
        if (count($norm) !== count(array_unique($norm))) {
            throw new DomainConflictException('validation_failed', 'أرقام الجرد القديمة تحتوي تكراراً', 422, ['legacy_numbers' => ['تكرار داخل المصفوفة']]);
        }
    }

    protected function guardSerial(?string $serial): void
    {
        if (AssetIdentifier::isPlaceholderSerial($serial)) {
            throw new DomainConflictException('validation_failed', 'الرقم التسلسلي قيمة وهمية غير مقبولة', 422, ['serial_no' => ['قيمة وهمية']]);
        }
    }

    protected function remember(int $userId, string $key, JsonResponse $response): JsonResponse
    {
        $this->idempotency->remember($userId, $key, $response);

        return $response;
    }

    protected function run(callable $fn)
    {
        try {
            return $fn();
        } catch (DomainConflictException $e) {
            return ApiResponse::error($e->errorCode, $e->getMessage(), $e->status, $e->fields);
        }
    }
}
