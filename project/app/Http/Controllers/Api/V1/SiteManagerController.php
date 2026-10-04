<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignSiteManagerRequest;
use App\Models\Site;
use App\Models\SiteManager;
use App\Services\AuditLogger;
use App\Services\IdempotencyService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Batch 3 §3.5. "One active manager per site" is enforced here as an
 * application-level rule inside a transaction: assigning a new manager
 * closes the previous open row (to = from - 1 day), matching Batch 2 §B2's
 * note that the DB-level enforcement mechanism was left open ([مفتوح]).
 */
class SiteManagerController extends Controller
{
    public function __construct(
        protected AuditLogger $audit,
        protected IdempotencyService $idempotency,
    ) {}

    public function index(Site $site)
    {
        return ApiResponse::ok($site->siteManagers()->orderByDesc('from')->get());
    }

    public function store(AssignSiteManagerRequest $request, Site $site)
    {
        $idKey = $request->header('Idempotency-Key');
        $actor = $request->user();

        if ($cached = $this->idempotency->find($actor->id, $idKey)) {
            return response()->json($cached->response_snapshot, $cached->response_status);
        }

        $from = Carbon::parse($request->input('from'));

        $manager = DB::transaction(function () use ($site, $request, $from, $actor) {
            $current = $site->siteManagers()->whereNull('to')->lockForUpdate()->first();
            if ($current) {
                $current->update(['to' => $from->copy()->subDay()->toDateString()]);
                $this->audit->record($actor->id, 'site_manager.ended', 'site_manager', $current->id);
            }

            $manager = SiteManager::create([
                'site_id' => $site->id,
                'user_id' => $request->input('user_id'),
                'from' => $from->toDateString(),
                'to' => null,
            ]);

            $this->audit->record(
                $actor->id, 'site_manager.assigned', 'site_manager', $manager->id,
                after: ['site_id' => $site->id, 'user_id' => $manager->user_id, 'from' => $manager->from->toDateString()]
            );

            return $manager;
        });

        $response = ApiResponse::created($manager);
        $this->idempotency->remember($actor->id, $idKey, $response);

        return $response;
    }

    public function end(Request $request, SiteManager $siteManager)
    {
        $actor = $request->user();

        if ($siteManager->to) {
            return ApiResponse::error('already_ended', 'انتهت هذه الفترة مسبقاً', 409);
        }

        $siteManager->update(['to' => now('UTC')->toDateString()]);

        $this->audit->record($actor->id, 'site_manager.ended', 'site_manager', $siteManager->id);

        return ApiResponse::ok($siteManager->fresh());
    }
}
