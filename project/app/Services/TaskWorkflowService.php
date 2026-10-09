<?php

namespace App\Services;

use App\Exceptions\DomainConflictException;
use App\Models\Asset;
use App\Models\Proposal;
use App\Models\RequestCycle;
use App\Models\Task;
use App\Models\TaskType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** M3 task lifecycle (Batch 1 §9 Task workflow, BR-M3-10..15). */
class TaskWorkflowService
{
    public function __construct(
        protected AuditLogger $audit,
        protected SequenceService $sequences,
        protected NotificationService $notify,
    ) {}

    public function create(User $actor, array $d, ?string $ip): Task
    {
        $type = TaskType::find($d['task_type_id']) ?? abort(404);
        if ($actor->role === 'secretary' && ! $type->is_administrative) {
            throw new DomainConflictException('forbidden', 'السكرتير ينشئ المهام الإدارية فقط', 403);
        }
        $siteId = $d['site_id'] ?? null;
        $cycle = null;
        if (! empty($d['request_cycle_id'])) {
            $cycle = RequestCycle::with('request')->find($d['request_cycle_id']) ?? abort(404);
            if ($cycle->isTerminal()) {
                throw new DomainConflictException('invalid_state', 'الدورة المرتبطة منتهية');
            }
            $siteId = $siteId ?? $cycle->request->origin_site_id;
        } elseif ($siteId === null) {
            throw new DomainConflictException('validation_failed', 'الموقع مطلوب للمهمة المستقلة', 422, ['site_id' => ['مطلوب للمهمة المستقلة']]);
        }
        if (! empty($d['asset_id'])) {
            $asset = Asset::find($d['asset_id']) ?? abort(404);
            if ($siteId !== null && (int) $asset->current_site_id !== (int) $siteId) {
                throw new DomainConflictException('validation_failed', 'الجهاز لا يتبع موقع المهمة', 422, ['asset_id' => ['الجهاز لا يتبع الموقع']]);
            }
        }

        return DB::transaction(function () use ($actor, $d, $ip, $siteId) {
            $task = Task::create([
                'ref_no' => $this->sequences->next('task'), 'task_type_id' => $d['task_type_id'], 'title' => $d['title'],
                'description' => $d['description'] ?? null, 'request_cycle_id' => $d['request_cycle_id'] ?? null,
                'asset_id' => $d['asset_id'] ?? null, 'site_id' => $siteId, 'status' => 'new', 'due_at' => $d['due_at'] ?? null,
                'created_by' => $actor->id, 'version' => 1,
            ]);
            $this->audit->record($actor->id, 'task.created', 'task', $task->id, null,
                ['ref_no' => $task->ref_no, 'task_type_id' => $task->task_type_id, 'request_cycle_id' => $task->request_cycle_id], null, [], 'web', $ip);

            return $task;
        });
    }

    public function assign(User $actor, Task $task, int $assigneeId, ?string $reason, ?int $version, ?string $ip, ?Proposal $proposal = null): Task
    {
        return DB::transaction(function () use ($actor, $task, $assigneeId, $reason, $version, $ip, $proposal) {
            $task = $this->lock($task);
            $this->assertVersion($task, $version);
            $this->assertStatus($task, ['new', 'assigned', 'in_progress', 'held'], 'حالة المهمة لا تسمح بالإسناد');

            $assignee = User::find($assigneeId);
            if (! $assignee || $assignee->status !== 'active') {
                abort(404);
            }
            $allowed = $actor->role === 'chairman' ? ['chairman', 'engineer', 'technician', 'secretary'] : ['engineer', 'technician'];
            if (! in_array($assignee->role, $allowed, true)) {
                throw new DomainConflictException('forbidden', 'لا صلاحية للإسناد لهذا الدور', 403);
            }
            if ($assignee->role === 'secretary' && ! $task->type->is_administrative) {
                throw new DomainConflictException('validation_failed', 'السكرتير يُكلَّف بالمهام الإدارية فقط', 422, ['assignee_id' => ['مهمة غير إدارية']]);
            }
            $isReassign = $task->assignee_id !== null;
            if ($isReassign && $task->assignee_id === $assignee->id) {
                throw new DomainConflictException('validation_failed', 'المسؤول الحالي هو نفسه', 422, ['assignee_id' => ['المسؤول الحالي هو نفسه']]);
            }
            if ($isReassign && trim((string) $reason) === '') {
                throw new DomainConflictException('validation_failed', 'سبب إعادة الإسناد مطلوب', 422, ['reason' => ['مطلوب']]);
            }
            $old = $task->assignee_id;
            $before = $task->status;
            $this->save($task, ['status' => 'assigned', 'assignee_id' => $assignee->id]);
            $this->audit->record($actor->id, $isReassign ? 'task.reassigned' : 'task.assigned', 'task', $task->id,
                ['status' => $before, 'assignee_id' => $old], ['status' => 'assigned', 'assignee_id' => $assignee->id, 'proposal_id' => $proposal?->id], $reason, [], 'web', $ip);
            $this->notify->send($isReassign ? [$old, $assignee->id] : [$assignee->id], $isReassign ? 'task.reassigned' : 'task.assigned', 'task', $task->id,
                ($isReassign ? 'تغيّر تكليف المهمة ' : 'تم تكليفك بالمهمة ').$task->ref_no, $actor->id);

            return $task->refresh();
        });
    }

    public function start(User $actor, Task $task, ?int $version, ?string $ip): Task
    {
        return $this->asAssignee($actor, $task, $version, ['assigned'], 'in_progress', 'task.started', [], $ip);
    }

    public function hold(User $actor, Task $task, ?int $version, ?string $ip): Task
    {
        return $this->asAssignee($actor, $task, $version, ['in_progress'], 'held', 'task.held', [], $ip);
    }

    public function resume(User $actor, Task $task, ?int $version, ?string $ip): Task
    {
        return $this->asAssignee($actor, $task, $version, ['held'], 'in_progress', 'task.resumed', [], $ip);
    }

    public function complete(User $actor, Task $task, string $summary, ?int $version, ?string $ip): Task
    {
        if (trim($summary) === '') {
            throw new DomainConflictException('validation_failed', 'ملخص النتيجة مطلوب', 422, ['result_summary' => ['مطلوب']]);
        }

        return $this->asAssignee($actor, $task, $version, ['in_progress'], 'completed', 'task.completed', ['result_summary' => $summary], $ip);
    }

    protected function asAssignee(User $actor, Task $task, ?int $version, array $allowed, string $to, string $event, array $extra, ?string $ip): Task
    {
        return DB::transaction(function () use ($actor, $task, $version, $allowed, $to, $event, $extra, $ip) {
            $task = $this->lock($task);
            if ($task->assignee_id !== $actor->id) {
                throw new DomainConflictException('forbidden', 'لست المسؤول الحالي بالمهمة', 403);
            }
            $this->assertStatus($task, $allowed, 'انتقال غير صالح');
            $this->assertVersion($task, $version);
            $before = $task->status;
            $this->save($task, ['status' => $to] + $extra);
            $this->audit->record($actor->id, $event, 'task', $task->id, ['status' => $before], ['status' => $to], null, [], 'web', $ip);

            return $task->refresh();
        });
    }

    public function cancel(User $actor, Task $task, array $d, ?int $version, ?string $ip, ?Proposal $proposal = null): Task
    {
        return DB::transaction(function () use ($actor, $task, $d, $version, $ip, $proposal) {
            $task = $this->lock($task);
            if ($task->isTerminal()) {
                throw new DomainConflictException('invalid_state', 'المهمة منتهية مسبقاً');
            }
            $this->assertVersion($task, $version);
            if ($actor->role !== 'chairman') {
                // BR-M3-11: secretary cancels new/assigned administrative tasks only
                if ($actor->role !== 'secretary' || ! in_array($task->status, ['new', 'assigned'], true) || ! $task->type->is_administrative) {
                    throw new DomainConflictException('forbidden', 'لا صلاحية لإلغاء المهمة في هذه الحالة', 403);
                }
            }
            if (in_array($task->status, ['in_progress', 'held'], true) && trim((string) ($d['work_done_summary'] ?? '')) === '') {
                throw new DomainConflictException('validation_failed', 'توثيق ما أُنجز مطلوب بعد البدء', 422, ['work_done_summary' => ['مطلوب بعد البدء']]);
            }
            $before = $task->status;
            $this->save($task, ['status' => 'cancelled']);
            $this->audit->record($actor->id, 'task.cancelled', 'task', $task->id, ['status' => $before],
                ['status' => 'cancelled', 'work_done_summary' => $d['work_done_summary'] ?? null, 'proposal_id' => $proposal?->id], $d['reason'], [], 'web', $ip);
            $this->notify->send([$task->assignee_id], 'task.cancelled', 'task', $task->id, "أُلغيت المهمة {$task->ref_no}", $actor->id);

            return $task->refresh();
        });
    }

    /** Called from request cancellation, inside its transaction (system-side, no extra authority checks). */
    public function cascadeCancel(User $actor, Task $task, string $reason, ?string $ip): void
    {
        $before = $task->status;
        $this->save($task, ['status' => 'cancelled']);
        $this->audit->record($actor->id, 'task.cancelled', 'task', $task->id, ['status' => $before], ['status' => 'cancelled', 'cascade' => true], $reason, [], 'web', $ip);
        $this->notify->send([$task->assignee_id], 'task.cancelled', 'task', $task->id, "أُلغيت المهمة {$task->ref_no} تبعاً لإلغاء الطلب", $actor->id);
    }

    public function propose(User $actor, Task $task, array $d, RequestWorkflowService $requests, ?string $ip): Proposal
    {
        return DB::transaction(function () use ($actor, $task, $d, $requests, $ip) {
            $task = $this->lock($task);
            if ($task->assignee_id !== $actor->id) {
                throw new DomainConflictException('forbidden', 'لست المسؤول الحالي بالمهمة', 403);
            }
            $this->assertStatus($task, ['assigned', 'in_progress', 'held'], 'حالة المهمة لا تسمح بالاقتراح');
            $proposal = $requests->makeProposal($actor, 'task', $task->id, $d);
            $this->audit->record($actor->id, "task.{$d['type']}_proposed", 'task', $task->id, null, ['proposal_id' => $proposal->id, 'type' => $d['type']], $d['reason'], [], 'web', $ip);
            $this->notify->send($this->notify->desk(), "task.{$d['type']}_proposed", 'task', $task->id, "اقتراح معلّق على المهمة {$task->ref_no}", $actor->id);

            return $proposal;
        });
    }

    protected function lock(Task $task): Task
    {
        return Task::whereKey($task->id)->lockForUpdate()->with('type')->firstOrFail();
    }

    protected function assertStatus(Task $task, array $allowed, string $msg): void
    {
        if (! in_array($task->status, $allowed, true)) {
            throw new DomainConflictException('invalid_state', $msg.' (الحالة الحالية: '.$task->status.')');
        }
    }

    protected function assertVersion(Task $task, ?int $version): void
    {
        if ($version !== null && $version !== $task->version) {
            throw new DomainConflictException('version_conflict', 'تم تعديل المهمة من مستخدم آخر، أعد التحميل');
        }
    }

    protected function save(Task $task, array $attrs): void
    {
        $task->fill($attrs);
        $task->version = $task->version + 1;
        $task->save();
    }
}
