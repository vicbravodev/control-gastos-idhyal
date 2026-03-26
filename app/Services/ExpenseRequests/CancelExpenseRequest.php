<?php

namespace App\Services\ExpenseRequests;

use App\Enums\ApprovalInstanceStatus;
use App\Enums\DocumentEventType;
use App\Enums\ExpenseRequestStatus;
use App\Models\DocumentEvent;
use App\Models\ExpenseRequest;
use App\Models\User;
use App\Services\Approvals\Exceptions\InvalidApprovalStateException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class CancelExpenseRequest
{
    public function cancel(ExpenseRequest $expenseRequest, User $actor, string $note): void
    {
        $note = trim($note);
        if ($note === '') {
            throw new InvalidApprovalStateException('A cancellation note is required.');
        }

        Gate::forUser($actor)->authorize('cancel', $expenseRequest);

        DB::transaction(function () use ($expenseRequest, $actor, $note): void {
            $locked = ExpenseRequest::query()
                ->whereKey($expenseRequest->getKey())
                ->lockForUpdate()
                ->with('approvals')
                ->firstOrFail();

            if (! in_array($locked->status, [
                ExpenseRequestStatus::Submitted,
                ExpenseRequestStatus::ApprovalInProgress,
            ], true)) {
                throw new InvalidApprovalStateException('This expense request cannot be cancelled in its current state.');
            }

            foreach ($locked->approvals as $approval) {
                if ($approval->status === ApprovalInstanceStatus::Pending) {
                    $approval->update(['status' => ApprovalInstanceStatus::Skipped]);
                }
            }

            $locked->update([
                'status' => ExpenseRequestStatus::Cancelled,
            ]);

            DocumentEvent::query()->create([
                'subject_type' => $locked->getMorphClass(),
                'subject_id' => $locked->getKey(),
                'event_type' => DocumentEventType::Cancellation,
                'actor_user_id' => $actor->id,
                'note' => $note,
            ]);
        });
    }
}
