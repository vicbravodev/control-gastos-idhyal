<?php

namespace App\Console\Commands;

use App\Enums\ExpenseRequestStatus;
use App\Enums\SettlementStatus;
use App\Models\Settlement;
use App\Services\ExpenseRequests\ExpenseRequestNotificationDispatcher;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('settlements:send-pending-reminders')]
#[Description('Enviar recordatorios diarios para balances en pending_user_return / pending_company_payment (≥24h desde creación).')]
class SendSettlementPendingRemindersCommand extends Command
{
    public function handle(ExpenseRequestNotificationDispatcher $dispatcher): int
    {
        /** @var int $sent */
        $sent = 0;

        $settlements = Settlement::query()
            ->whereIn('status', [
                SettlementStatus::PendingUserReturn,
                SettlementStatus::PendingCompanyPayment,
            ])
            ->where('created_at', '<=', now()->subDay())
            ->with(['expenseReport.expenseRequest.user'])
            ->get();

        foreach ($settlements as $settlement) {
            $report = $settlement->expenseReport;
            $expenseRequest = $report?->expenseRequest;
            if ($expenseRequest === null) {
                continue;
            }

            if ($expenseRequest->status !== ExpenseRequestStatus::SettlementPending) {
                continue;
            }

            $dispatcher->notifySettlementPendingReminders($expenseRequest, $settlement);
            $sent++;
        }

        $this->info(__('Recordatorios enviados: :count', ['count' => (string) $sent]));

        return self::SUCCESS;
    }
}
