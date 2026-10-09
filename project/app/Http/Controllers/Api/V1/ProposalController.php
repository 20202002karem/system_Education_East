<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\M3Responses;
use App\Http\Controllers\Controller;
use App\Http\Requests\AcceptProposalRequest;
use App\Http\Requests\RejectProposalRequest;
use App\Http\Resources\ProposalResource;
use App\Models\Proposal;
use App\Services\ProposalService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** M3-API-027..029 — chairman/secretary only (route middleware). */
class ProposalController extends Controller
{
    use M3Responses;

    public function __construct(protected ProposalService $service) {}

    public function index(Request $request)
    {
        $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'accepted', 'rejected'])],
            'subject_type' => ['nullable', Rule::in(['request', 'task'])],
        ]);
        $query = Proposal::query()->where('status', $request->query('status', 'pending'))->orderByDesc('created_at')->orderByDesc('id');
        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->query('subject_type'));
        }

        return $this->paginated($request, $query, fn ($rows) => ProposalResource::collection($rows)->resolve());
    }

    public function accept(AcceptProposalRequest $request, int $id)
    {
        $p = Proposal::find($id) ?? abort(404);

        return $this->idempotent($request, fn () => ApiResponse::ok(ProposalResource::make(
            $this->service->accept($request->user(), $p, $request->validated()['new_assignee_id'] ?? null, $request->ip()))->resolve()));
    }

    public function reject(RejectProposalRequest $request, int $id)
    {
        $p = Proposal::find($id) ?? abort(404);

        return $this->idempotent($request, fn () => ApiResponse::ok(ProposalResource::make($this->service->reject($request->user(), $p))->resolve()));
    }
}
