<?php

namespace App\Console\Commands;

use App\Enums\ApprovalInstanceStatus;
use App\Enums\DocumentEventType;
use App\Enums\VacationRequestStatus;
use App\Models\DocumentEvent;
use App\Models\VacationRequest;
use App\Notifications\VacationRequests\VacationRequestExpiredNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

#[Signature('vacation-requests:expire-stale-pending')]
#[Description('Expirar solicitudes de vacaciones en submitted/approval_in_progress que llevan más de N días sin resolverse, devolviendo los días al saldo disponible.')]
class ExpireStaleVacationRequestsCommand extends Command
{
    public function handle(): int
    {
        $days = (int) config('vacation_requests.pending_expiration_days');
        $threshold = now()->subDays($days);

        $stale = VacationRequest::query()
            ->whereIn('status', [
                VacationRequestStatus::Submitted,
                VacationRequestStatus::ApprovalInProgress,
            ])
            ->where('created_at', '<=', $threshold)
            ->with(['user', 'approvals'])
            ->get();

        $expired = 0;

        foreach ($stale as $vacationRequest) {
            DB::transaction(function () use ($vacationRequest): void {
                foreach ($vacationRequest->approvals as $approval) {
                    if ($approval->status === ApprovalInstanceStatus::Pending) {
                        $approval->update(['status' => ApprovalInstanceStatus::Skipped]);
                    }
                }

                $vacationRequest->update([
                    'status' => VacationRequestStatus::Expired,
                ]);

                DocumentEvent::query()->create([
                    'subject_type' => $vacationRequest->getMorphClass(),
                    'subject_id' => $vacationRequest->getKey(),
                    'event_type' => DocumentEventType::VacationRequestExpired,
                    'actor_user_id' => null,
                    'note' => __('Expirada automáticamente tras :days días sin aprobación.', ['days' => (int) config('vacation_requests.pending_expiration_days')]),
                    'metadata' => [
                        'reason' => 'expired_by_inactivity',
                        'expiration_days' => (int) config('vacation_requests.pending_expiration_days'),
                    ],
                ]);
            });

            DB::afterCommit(function () use ($vacationRequest, $days): void {
                if ($vacationRequest->user !== null) {
                    Notification::send(
                        $vacationRequest->user,
                        new VacationRequestExpiredNotification($vacationRequest, $days),
                    );
                }
            });

            $expired++;
        }

        $this->info(__('Solicitudes expiradas: :count', ['count' => (string) $expired]));

        return self::SUCCESS;
    }
}
