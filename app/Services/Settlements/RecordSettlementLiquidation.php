<?php

namespace App\Services\Settlements;

use App\Enums\DocumentEventType;
use App\Enums\ExpenseReportStatus;
use App\Enums\ExpenseRequestStatus;
use App\Enums\SettlementStatus;
use App\Models\Attachment;
use App\Models\DocumentEvent;
use App\Models\ExpenseRequest;
use App\Models\Settlement;
use App\Models\User;
use App\Services\ExpenseRequests\ExpenseRequestNotificationDispatcher;
use App\Services\Settlements\Exceptions\InvalidSettlementException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final class RecordSettlementLiquidation
{
    public function __construct(
        private readonly ExpenseRequestNotificationDispatcher $notifications,
    ) {}

    /**
     * @throws InvalidSettlementException
     */
    public function record(
        ExpenseRequest $expenseRequest,
        User $actor,
        UploadedFile $evidence,
    ): Settlement {
        if ($expenseRequest->status !== ExpenseRequestStatus::SettlementPending) {
            throw new InvalidSettlementException(__('La solicitud no está pendiente de liquidación.'));
        }

        $report = $expenseRequest->expenseReport;
        if ($report === null || $report->status !== ExpenseReportStatus::Approved) {
            throw new InvalidSettlementException(__('La comprobación no está aprobada.'));
        }

        $settlement = $report->settlement;
        if ($settlement === null) {
            throw new InvalidSettlementException(__('No hay balance registrado para esta solicitud.'));
        }

        if (! in_array($settlement->status, [
            SettlementStatus::PendingUserReturn,
            SettlementStatus::PendingCompanyPayment,
        ], true)) {
            throw new InvalidSettlementException(__('El balance no admite registrar liquidación en este momento.'));
        }

        $settlement = DB::transaction(function () use (
            $expenseRequest,
            $actor,
            $evidence,
            $settlement,
        ): Settlement {
            $path = $evidence->store('settlements/'.$settlement->id, 'local');
            if ($path === false) {
                throw new InvalidSettlementException(__('No se pudo guardar la evidencia de liquidación.'));
            }

            Attachment::query()->create([
                'attachable_type' => $settlement->getMorphClass(),
                'attachable_id' => $settlement->getKey(),
                'uploaded_by_user_id' => $actor->id,
                'disk' => 'local',
                'path' => $path,
                'original_filename' => $evidence->getClientOriginalName(),
                'mime_type' => $evidence->getClientMimeType(),
                'size_bytes' => $evidence->getSize(),
            ]);

            $settlement->update([
                'status' => SettlementStatus::Settled,
            ]);

            DocumentEvent::query()->create([
                'subject_type' => $expenseRequest->getMorphClass(),
                'subject_id' => $expenseRequest->getKey(),
                'event_type' => DocumentEventType::SettlementLiquidationRecorded,
                'actor_user_id' => $actor->id,
                'note' => '-',
                'metadata' => [
                    'settlement_id' => $settlement->id,
                    'expense_report_id' => $settlement->expense_report_id,
                ],
            ]);

            return $settlement->fresh();
        });

        DB::afterCommit(function () use ($expenseRequest, $settlement): void {
            $fresh = $expenseRequest->fresh(['user']);
            $freshSettlement = $settlement->fresh();
            if ($fresh !== null && $freshSettlement !== null) {
                $this->notifications->notifyRequesterOnSettlementLiquidated($fresh, $freshSettlement);
            }
        });

        return $settlement;
    }
}
