<?php

namespace App\Services;

use App\Exceptions\DomainConflictException;
use App\Models\Proposal;
use App\Models\ServiceRequest;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** APID-M3-02: accept/reject delegate to the real assignment / cancellation logic (single transaction). */
class ProposalService
{
    public function __construct(protected RequestWorkflowService $requests, protected TaskWorkflowService $tasks) {}

    public function accept(User $actor, Proposal $proposal, ?int $newAssigneeId, ?string $ip): Proposal
    {
        return DB::transaction(function () use ($actor, $proposal, $newAssigneeId, $ip) {
            $proposal = Proposal::whereKey($proposal->id)->lockForUpdate()->firstOrFail();
            if ($proposal->status !== 'pending') {
                throw new DomainConflictException('invalid_state', 'الاقتراح لم يعد معلّقاً');
            }
            if ($proposal->type === 'reassign' && ! $newAssigneeId) {
                throw new DomainConflictException('validation_failed', 'المكلّف الجديد مطلوب', 422, ['new_assignee_id' => ['مطلوب']]);
            }
            $subject = $proposal->subject_type === 'request'
                ? ServiceRequest::findOrFail($proposal->subject_id)
                : Task::findOrFail($proposal->subject_id);
            $cancel = ['reason_category' => 'proposal', 'reason_text' => $proposal->reason, 'reason' => $proposal->reason, 'work_done_summary' => $proposal->work_done_summary];

            if ($proposal->subject_type === 'request') {
                $proposal->type === 'reassign'
                    ? $this->requests->assign($actor, $subject, $newAssigneeId, $proposal->reason, null, $ip, $proposal)
                    : $this->requests->cancel($actor, $subject, $cancel, null, $ip);
            } else {
                $proposal->type === 'reassign'
                    ? $this->tasks->assign($actor, $subject, $newAssigneeId, $proposal->reason, null, $ip, $proposal)
                    : $this->tasks->cancel($actor, $subject, $cancel, null, $ip, $proposal);
            }
            $proposal->update(['status' => 'accepted', 'decided_by' => $actor->id, 'decided_at' => now('UTC')]);

            return $proposal->refresh();
        });
    }

    public function reject(User $actor, Proposal $proposal): Proposal
    {
        return DB::transaction(function () use ($actor, $proposal) {
            $proposal = Proposal::whereKey($proposal->id)->lockForUpdate()->firstOrFail();
            if ($proposal->status !== 'pending') {
                throw new DomainConflictException('invalid_state', 'الاقتراح لم يعد معلّقاً');
            }
            $proposal->update(['status' => 'rejected', 'decided_by' => $actor->id, 'decided_at' => now('UTC')]);

            return $proposal->refresh();
        });
    }
}
