<?php

namespace App\Services;

use App\Exceptions\DomainConflictException;
use App\Models\Proposal;
use App\Models\RequestAssignment;
use App\Models\RequestCancellation;
use App\Models\RequestCycle;
use App\Models\RequestNote;
use App\Models\ServiceRequest;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;
use App\Support\RequestAccess;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * M3 request lifecycle (Batch 1 §9, Batch 3 §17). Every transition: lock the current cycle,
 * check authority (403) then state (409) then optimistic version (409), write, audit, notify.
 * No free PATCH exists (DD-M3-02).
 */
class RequestWorkflowService
{
    public function __construct(
        protected AuditLogger $audit,
        protected SequenceService $sequences,
        protected NotificationService $notify,
        protected TaskWorkflowService $tasks,
    ) {}

    // ---- creation (M3-API-001) ----

    public function create(User $actor, array $d, ?string $ip): ServiceRequest
    {
        return DB::transaction(function () use ($actor, $d, $ip) {
            $isManager = $actor->role === 'school_manager';
            $request = ServiceRequest::create([
                'ref_no' => $this->sequences->next('request'),
                'origin_site_id' => $d['origin_site_id'],
                'asset_id' => $d['asset_id'] ?? null,
                'requester_user_id' => $isManager ? $actor->id : null,
                'requester_name' => $isManager ? null : ($d['requester_name'] ?? null),
                'registered_by_user_id' => $actor->id,
                'channel_id' => $d['channel_id'],
                'suggested_priority' => $d['suggested_priority'] ?? null,
                'description' => $d['description'],
                'current_cycle_no' => 1,
                'reopen_count' => 0,
            ]);
            $cycle = RequestCycle::create([
                'request_id' => $request->id, 'cycle_no' => 1, 'status' => 'new', 'open_marker' => 1, 'version' => 1,
            ]);
            $this->audit->record($actor->id, 'request.created', 'request', $request->id, null, [
                'ref_no' => $request->ref_no, 'origin_site_id' => $request->origin_site_id, 'asset_id' => $request->asset_id,
                'channel_id' => $request->channel_id, 'cycle_status' => $cycle->status,
            ], null, [], 'web', $ip);
            $this->notify->send($this->notify->desk(), 'request.created', 'request', $request->id, "طلب جديد بانتظار الفرز: {$request->ref_no}", $actor->id);

            return $request;
        });
    }

    // ---- triage (M3-API-004) ----

    public function triage(User $actor, ServiceRequest $request, int $typeId, string $priority, ?int $version, ?string $ip): RequestCycle
    {
        return DB::transaction(function () use ($actor, $request, $typeId, $priority, $version, $ip) {
            $cycle = $this->lockCycle($request);
            $this->assertVersion($cycle, $version);
            $this->assertStatus($cycle, ['new'], 'الدورة ليست بحالة "جديد"');

            $request->update(['request_type_id' => $typeId, 'priority' => $priority]);
            $this->touch($cycle, ['status' => 'triaged']);
            $this->audit->record($actor->id, 'request.triaged', 'request', $request->id,
                ['status' => 'new'], ['status' => 'triaged', 'request_type_id' => $typeId, 'priority' => $priority], null, [], 'web', $ip);
            $this->notify->send($this->notify->desk(), 'request.triaged', 'request', $request->id, "تم فرز الطلب {$request->ref_no} وهو بانتظار الإسناد", $actor->id);

            return $cycle->refresh();
        });
    }

    // ---- assignment / reassignment (M3-API-005, also used by proposal acceptance) ----

    public function assign(User $actor, ServiceRequest $request, int $assigneeId, ?string $reason, ?int $version, ?string $ip, ?Proposal $proposal = null): array
    {
        return DB::transaction(function () use ($actor, $request, $assigneeId, $reason, $version, $ip, $proposal) {
            $cycle = $this->lockCycle($request);
            $this->assertVersion($cycle, $version);
            $this->assertStatus($cycle, ['triaged', 'assigned', 'in_progress', 'held', 'reopened_pending_assignment'], 'حالة الدورة لا تسمح بالإسناد');

            $assignee = $this->resolveAssignee($actor, $request, $assigneeId);
            $isReassign = $cycle->assignee_user_id !== null;
            if ($isReassign && $cycle->assignee_user_id === $assignee->id) {
                throw new DomainConflictException('validation_failed', 'المكلّف الحالي هو نفسه', 422, ['assignee_id' => ['المكلّف الحالي هو نفسه']]);
            }
            if ($isReassign && ($reason === null || trim($reason) === '')) {
                throw new DomainConflictException('validation_failed', 'سبب إعادة الإسناد مطلوب', 422, ['reason' => ['سبب إعادة الإسناد مطلوب']]);
            }

            $now = now('UTC');
            $oldAssignee = $cycle->assignee_user_id;
            RequestAssignment::where('cycle_id', $cycle->id)->whereNull('to')->update(['to' => $now]);
            $assignment = RequestAssignment::create([
                'cycle_id' => $cycle->id, 'assignee_id' => $assignee->id, 'assigned_by' => $actor->id,
                'reason' => $reason, 'from' => $now,
            ]);
            $before = $cycle->status;
            $this->touch($cycle, ['status' => 'assigned', 'assignee_user_id' => $assignee->id]);

            $this->audit->record($actor->id, $isReassign ? 'request.reassigned' : 'request.assigned', 'request', $request->id,
                ['status' => $before, 'assignee_user_id' => $oldAssignee],
                ['status' => 'assigned', 'assignee_user_id' => $assignee->id, 'proposal_id' => $proposal?->id],
                $reason, [], 'web', $ip);

            if ($isReassign) {
                $this->notify->send([$oldAssignee, $assignee->id], 'request.reassigned', 'request', $request->id, "تغيّر تكليف الطلب {$request->ref_no}", $actor->id);
            } else {
                $this->notify->send([$assignee->id], 'request.assigned', 'request', $request->id, "تم تكليفك بالطلب {$request->ref_no}", $actor->id);
            }

            return [$cycle->refresh(), $assignment];
        });
    }

    protected function resolveAssignee(User $actor, ServiceRequest $request, int $assigneeId): User
    {
        $assignee = User::find($assigneeId);
        if (! $assignee || $assignee->status !== 'active') {
            abort(404);
        }
        if ($actor->role === 'secretary' && ($assignee->id === $actor->id || $assignee->role === 'chairman')) {
            throw new DomainConflictException('forbidden', 'لا يجوز للسكرتير الإسناد لنفسه أو لرئيس القسم', 403);
        }
        if (! in_array($assignee->role, ['chairman', 'engineer', 'technician'], true)) {
            throw new DomainConflictException('validation_failed', 'لا يمكن إسناد الطلب لهذا الدور', 422, ['assignee_id' => ['دور غير صالح للإسناد']]);
        }
        if (! RequestAccess::coversSite($assignee, (int) $request->origin_site_id)) {
            abort(404); // assignee outside the scope that covers the request site (Batch 3 M3-API-005)
        }

        return $assignee;
    }

    // ---- execution (M3-API-006..009) ----

    public function start(User $actor, ServiceRequest $request, ?int $version, ?string $ip): RequestCycle
    {
        return $this->actAsAssignee($actor, $request, $version, ['assigned'], 'الدورة ليست بحالة "مُسنَد"', function (RequestCycle $c) use ($actor, $request, $ip) {
            $this->touch($c, ['status' => 'in_progress', 'started_at' => $c->started_at ?? now('UTC')]);
            $this->audit->record($actor->id, 'request.started', 'request', $request->id, ['status' => 'assigned'], ['status' => 'in_progress'], null, [], 'web', $ip);
        });
    }

    public function hold(User $actor, ServiceRequest $request, ?string $reason, ?int $version, ?string $ip): RequestCycle
    {
        return $this->actAsAssignee($actor, $request, $version, ['in_progress'], 'الدورة ليست بحالة "قيد التنفيذ"', function (RequestCycle $c) use ($actor, $request, $reason, $ip) {
            $this->touch($c, ['status' => 'held', 'hold_reason' => $reason]);
            $this->audit->record($actor->id, 'request.held', 'request', $request->id, ['status' => 'in_progress'], ['status' => 'held'], $reason, [], 'web', $ip);
        });
    }

    public function resume(User $actor, ServiceRequest $request, ?int $version, ?string $ip): RequestCycle
    {
        return $this->actAsAssignee($actor, $request, $version, ['held'], 'الدورة ليست بحالة "معلّق"', function (RequestCycle $c) use ($actor, $request, $ip) {
            $this->touch($c, ['status' => 'in_progress', 'hold_reason' => null]);
            $this->audit->record($actor->id, 'request.resumed', 'request', $request->id, ['status' => 'held'], ['status' => 'in_progress'], null, [], 'web', $ip);
        });
    }

    public function close(User $actor, ServiceRequest $request, array $d, ?int $version, ?string $ip): RequestCycle
    {
        return $this->actAsAssignee($actor, $request, $version, ['in_progress'], 'الإغلاق ممكن من "قيد التنفيذ" فقط', function (RequestCycle $c) use ($actor, $request, $d, $ip) {
            // BR-M3-07 / BR-M3-13: non-terminal child tasks block closure.
            if (Task::where('request_cycle_id', $c->id)->whereIn('status', Task::NON_TERMINAL)->exists()) {
                throw new DomainConflictException('open_child_tasks', 'توجد مهام فرعية غير منتهية مرتبطة بالدورة');
            }
            $this->touch($c, [
                'status' => 'closed', 'closed_at' => now('UTC'), 'open_marker' => null,
                'closure_action' => $d['closure_action'], 'closure_result' => $d['closure_result'],
                'closure_effort_minutes' => $d['closure_effort_minutes'],
            ]);
            $this->audit->record($actor->id, 'request.closed', 'request', $request->id, ['status' => 'in_progress'],
                ['status' => 'closed', 'closure_effort_minutes' => $d['closure_effort_minutes']], null, ['sensitive' => true], 'web', $ip);
            $this->notify->send([$request->requester_user_id, $request->registered_by_user_id], 'request.closed', 'request', $request->id, "تم إغلاق الطلب {$request->ref_no}", $actor->id);
        });
    }

    protected function actAsAssignee(User $actor, ServiceRequest $request, ?int $version, array $allowed, string $stateMsg, callable $work): RequestCycle
    {
        return DB::transaction(function () use ($actor, $request, $version, $allowed, $stateMsg, $work) {
            $cycle = $this->lockCycle($request);
            // authority is evaluated at execution time against the locked row (Batch 3 §8)
            if ($cycle->assignee_user_id !== $actor->id) {
                throw new DomainConflictException('forbidden', 'لست المكلّف الحالي بالطلب', 403);
            }
            $this->assertStatus($cycle, $allowed, $stateMsg);
            $this->assertVersion($cycle, $version);
            $work($cycle);

            return $cycle->refresh();
        });
    }

    // ---- cancellation (M3-API-010) ----

    public function cancel(User $actor, ServiceRequest $request, array $d, ?int $version, ?string $ip): RequestCycle
    {
        return DB::transaction(function () use ($actor, $request, $d, $version, $ip) {
            $cycle = $this->lockCycle($request);
            if ($cycle->isTerminal() || RequestCancellation::where('request_id', $request->id)->exists()) {
                throw new DomainConflictException('invalid_state', 'الطلب مغلق أو ملغى مسبقاً');
            }
            $this->assertVersion($cycle, $version);

            if ($actor->role !== 'chairman') {
                if (! in_array($actor->role, ['school_manager', 'secretary'], true)
                    || ! in_array($cycle->status, ['new', 'triaged'], true)
                    || ($actor->role === 'school_manager' && $request->requester_user_id !== $actor->id)) {
                    throw new DomainConflictException('forbidden', 'لا صلاحية للإلغاء في هذه المرحلة', 403);
                }
            }
            $afterStart = in_array($cycle->status, ['in_progress', 'held'], true);
            if ($afterStart && trim((string) ($d['work_done_summary'] ?? '')) === '') {
                throw new DomainConflictException('validation_failed', 'توثيق ما أُنجز مطلوب بعد البدء', 422, ['work_done_summary' => ['مطلوب بعد البدء']]);
            }

            try {
                RequestCancellation::create([
                    'request_id' => $request->id, 'reason_category' => $d['reason_category'], 'reason_text' => $d['reason_text'],
                    'work_done_summary' => $d['work_done_summary'] ?? null, 'cancelled_by' => $actor->id,
                    'stage' => $afterStart ? 'after_start' : 'before_assignment', 'cancelled_at' => now('UTC'),
                ]);
            } catch (QueryException) {
                throw new DomainConflictException('already_cancelled', 'الطلب ملغى مسبقاً');
            }

            $before = $cycle->status;
            RequestAssignment::where('cycle_id', $cycle->id)->whereNull('to')->update(['to' => now('UTC')]);
            $this->touch($cycle, ['status' => 'cancelled', 'open_marker' => null]);
            $this->audit->record($actor->id, 'request.cancelled', 'request', $request->id, ['status' => $before], ['status' => 'cancelled'],
                $d['reason_text'], ['sensitive' => true], 'web', $ip);

            // child tasks are cancelled sequentially with the request (Batch 3 M3-API-010)
            foreach (Task::where('request_cycle_id', $cycle->id)->whereIn('status', Task::NON_TERMINAL)->lockForUpdate()->get() as $task) {
                $this->tasks->cascadeCancel($actor, $task, 'إلغاء الطلب الأب', $ip);
            }
            $this->notify->send(array_merge([$cycle->assignee_user_id, $request->requester_user_id], $this->notify->desk()), 'request.cancelled', 'request', $request->id,
                "تم إلغاء الطلب {$request->ref_no}", $actor->id);

            return $cycle->refresh();
        });
    }

    // ---- reopen (M3-API-011) ----

    public function reopen(User $actor, ServiceRequest $request, string $reason, ?string $ip): RequestCycle
    {
        return DB::transaction(function () use ($actor, $request, $reason, $ip) {
            $cycle = $this->lockCycle($request);
            if ($cycle->status !== 'closed') {
                throw new DomainConflictException('invalid_state', 'آخر دورة ليست مغلقة');
            }
            if ($actor->role === 'school_manager') {
                $days = (int) (Setting::find('reopen_window_days')?->value ?? 7);
                if ($request->requester_user_id !== $actor->id || $cycle->closed_at === null || now('UTC')->greaterThan($cycle->closed_at->copy()->addDays($days))) {
                    throw new DomainConflictException('forbidden', 'انتهت مهلة الاعتراض أو لست مقدّم الطلب', 403);
                }
            }
            $new = RequestCycle::create([
                'request_id' => $request->id, 'cycle_no' => $cycle->cycle_no + 1, 'status' => 'reopened_pending_assignment',
                'open_marker' => 1, 'version' => 1,
            ]);
            $request->update(['current_cycle_no' => $new->cycle_no, 'reopen_count' => $request->reopen_count + 1]);
            $this->audit->record($actor->id, 'request.reopened', 'request', $request->id, ['cycle_no' => $cycle->cycle_no, 'status' => 'closed'],
                ['cycle_no' => $new->cycle_no, 'status' => $new->status], $reason, [], 'web', $ip);
            $this->notify->send(array_merge([$cycle->assignee_user_id], $this->notify->desk()), 'request.reopened', 'request', $request->id,
                "أُعيد فتح الطلب {$request->ref_no}", $actor->id);

            return $new;
        });
    }

    // ---- notes (M3-API-014) ----

    public function addNote(User $actor, ServiceRequest $request, string $visibility, string $body): RequestNote
    {
        if ($visibility === 'internal' && $actor->role === 'school_manager') {
            throw new DomainConflictException('forbidden', 'لا يجوز لمدير المدرسة كتابة ملاحظة داخلية', 403);
        }
        $cycle = $request->cycles()->orderByDesc('cycle_no')->firstOrFail();

        return RequestNote::create(['cycle_id' => $cycle->id, 'author_id' => $actor->id, 'visibility' => $visibility, 'body' => $body]);
    }

    // ---- proposals (M3-API-016) ----

    public function propose(User $actor, ServiceRequest $request, array $d, ?string $ip): Proposal
    {
        return DB::transaction(function () use ($actor, $request, $d, $ip) {
            $cycle = $this->lockCycle($request);
            if ($cycle->assignee_user_id !== $actor->id) {
                throw new DomainConflictException('forbidden', 'لست المكلّف الحالي بالطلب', 403);
            }
            $this->assertStatus($cycle, ['assigned', 'in_progress', 'held'], 'حالة الدورة لا تسمح بالاقتراح');
            $proposal = $this->makeProposal($actor, 'request', $request->id, $d);
            $this->audit->record($actor->id, "request.{$d['type']}_proposed", 'request', $request->id, null,
                ['proposal_id' => $proposal->id, 'type' => $d['type']], $d['reason'], [], 'web', $ip);
            $this->notify->send($this->notify->desk(), "request.{$d['type']}_proposed", 'request', $request->id, "اقتراح معلّق على الطلب {$request->ref_no}", $actor->id);

            return $proposal;
        });
    }

    public function makeProposal(User $actor, string $subjectType, int $subjectId, array $d): Proposal
    {
        if ($d['type'] === 'cancel' && trim((string) ($d['work_done_summary'] ?? '')) === '') {
            throw new DomainConflictException('validation_failed', 'ملخص ما أُنجز مطلوب لاقتراح الإلغاء', 422, ['work_done_summary' => ['مطلوب']]);
        }

        return Proposal::create([
            'type' => $d['type'], 'subject_type' => $subjectType, 'subject_id' => $subjectId, 'proposer_id' => $actor->id,
            'reason' => $d['reason'], 'work_done_summary' => $d['work_done_summary'] ?? null, 'status' => 'pending',
        ]);
    }

    // ---- helpers ----

    protected function lockCycle(ServiceRequest $request): RequestCycle
    {
        return RequestCycle::where('request_id', $request->id)->orderByDesc('cycle_no')->lockForUpdate()->firstOrFail();
    }

    protected function assertStatus(RequestCycle $cycle, array $allowed, string $message): void
    {
        if (! in_array($cycle->status, $allowed, true)) {
            throw new DomainConflictException('invalid_state', $message.' (الحالة الحالية: '.$cycle->status.')');
        }
    }

    protected function assertVersion(RequestCycle $cycle, ?int $version): void
    {
        if ($version !== null && $version !== $cycle->version) {
            throw new DomainConflictException('version_conflict', 'تم تعديل الطلب من مستخدم آخر، أعد التحميل');
        }
    }

    protected function touch(RequestCycle $cycle, array $attrs): void
    {
        $cycle->fill($attrs);
        $cycle->version = $cycle->version + 1;
        $cycle->save();
    }
}
