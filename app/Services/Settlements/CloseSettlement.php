<?php

namespace App\Services\Settlements;

use App\Enums\DocumentEventType;
use App\Enums\ExpenseReportStatus;
use App\Enums\ExpenseRequestStatus;
use App\Enums\SettlementStatus;
use App\Models\DocumentEvent;
use App\Models\ExpenseRequest;
use App\Models\Settlement;
use App\Models\User;
use App\Services\ExpenseRequests\ExpenseRequestNotificationDispatcher;
use App\Services\Settlements\Exceptions\InvalidSettlementException;
use Illuminate\Support\Facades\DB;

final class CloseSettlement
{
    public function __construct(
        private readonly ExpenseRequestNotificationDispatcher $notifications,
    ) {}

    /**
     * @throws InvalidSettlementException
     */
    public function close(
        ExpenseRequest $expenseRequest,
        User $actor,
        ?string $note,
    ): Settlement {
        $report = $expenseRequest->expenseReport;
        if ($report === null || $report->status !== ExpenseReportStatus::Approved) {
            throw new InvalidSettlementException(__('La comprobación no está aprobada.'));
        }

        $settlement = $report->settlement;
        if ($settlement === null) {
            throw new InvalidSettlementException(__('No hay balance registrado para esta solicitud.'));
        }

        if ($settlement->status === SettlementStatus::Closed) {
            throw new InvalidSettlementException(__('El balance ya está cerrado.'));
        }

        if ($settlement->status !== SettlementStatus::Settled) {
            throw new InvalidSettlementException(__('El balance debe estar liquidado antes de cerrar.'));
        }

        if ($expenseRequest->status !== ExpenseRequestStatus::SettlementPending) {
            throw new InvalidSettlementException(__('La solicitud no está en liquidación pendiente.'));
        }

        $settlement = DB::transaction(function () use (
            $expenseRequest,
            $actor,
            $note,
            $settlement,
        ): Settlement {
            $settlement->update([
                'status' => SettlementStatus::Closed,
            ]);

            $expenseRequest->update([
                'status' => ExpenseRequestStatus::Closed,
            ]);

            DocumentEvent::query()->create([
                'subject_type' => $expenseRequest->getMorphClass(),
                'subject_id' => $expenseRequest->getKey(),
                'event_type' => DocumentEventType::SettlementClosed,
                'actor_user_id' => $actor->id,
                'note' => $note !== null && $note !== '' ? $note : '-',
                'metadata' => [
                    'settlement_id' => $settlement->id,
                ],
            ]);

            return $settlement->fresh();
        });

        DB::afterCommit(function () use ($expenseRequest, $settlement): void {
            $fresh = $expenseRequest->fresh(['user']);
            $freshSettlement = $settlement->fresh();
            if ($fresh !== null && $freshSettlement !== null) {
                $this->notifications->notifyRequesterOnSettlementClosed($fresh, $freshSettlement);
            }
        });

        return $settlement;
    }
}
