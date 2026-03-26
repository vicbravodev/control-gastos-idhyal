<?php

namespace App\Services\Payments;

use App\Enums\DocumentEventType;
use App\Enums\ExpenseRequestStatus;
use App\Enums\PaymentMethod;
use App\Models\Attachment;
use App\Models\DocumentEvent;
use App\Models\ExpenseRequest;
use App\Models\Payment;
use App\Models\User;
use App\Services\Budgets\ExpenseRequestBudgetLedgerWriter;
use App\Services\ExpenseRequests\ExpenseRequestNotificationDispatcher;
use App\Services\Payments\Exceptions\InvalidExpenseRequestPaymentException;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final class RecordExpenseRequestPayment
{
    public function __construct(
        private readonly ExpenseRequestNotificationDispatcher $notifications,
        private readonly ExpenseRequestBudgetLedgerWriter $budgetLedger,
    ) {}

    /**
     * @throws InvalidExpenseRequestPaymentException
     */
    public function record(
        ExpenseRequest $expenseRequest,
        User $actor,
        int $amountCents,
        PaymentMethod $paymentMethod,
        \DateTimeInterface $paidOn,
        ?string $transferReference,
        UploadedFile $evidence,
    ): Payment {
        if ($expenseRequest->status !== ExpenseRequestStatus::PendingPayment) {
            throw new InvalidExpenseRequestPaymentException(__('La solicitud no está pendiente de pago.'));
        }

        if ($expenseRequest->payments()->exists()) {
            throw new InvalidExpenseRequestPaymentException(__('Esta solicitud ya tiene un pago registrado.'));
        }

        $approved = $expenseRequest->approved_amount_cents;
        if ($approved === null || $amountCents !== $approved) {
            throw new InvalidExpenseRequestPaymentException(__('El monto debe coincidir con el monto aprobado.'));
        }

        if ($paymentMethod === PaymentMethod::Transfer && ($transferReference === null || $transferReference === '')) {
            throw new InvalidExpenseRequestPaymentException(__('La referencia de transferencia es obligatoria.'));
        }

        $payment = DB::transaction(function () use (
            $expenseRequest,
            $actor,
            $amountCents,
            $paymentMethod,
            $paidOn,
            $transferReference,
            $evidence,
        ): Payment {
            $payment = Payment::query()->create([
                'expense_request_id' => $expenseRequest->id,
                'recorded_by_user_id' => $actor->id,
                'amount_cents' => $amountCents,
                'payment_method' => $paymentMethod,
                'paid_on' => $paidOn,
                'transfer_reference' => $transferReference,
            ]);

            $path = $evidence->store('payments/'.$payment->id, 'local');
            if ($path === false) {
                throw new InvalidExpenseRequestPaymentException(__('No se pudo guardar la evidencia del pago.'));
            }

            Attachment::query()->create([
                'attachable_type' => $payment->getMorphClass(),
                'attachable_id' => $payment->getKey(),
                'uploaded_by_user_id' => $actor->id,
                'disk' => 'local',
                'path' => $path,
                'original_filename' => $evidence->getClientOriginalName(),
                'mime_type' => $evidence->getClientMimeType(),
                'size_bytes' => $evidence->getSize(),
            ]);

            // Spec funcional §3.1: pending_payment → paid → awaiting_expense_report (sistema, misma operación).
            $expenseRequest->update([
                'status' => ExpenseRequestStatus::Paid,
            ]);
            $expenseRequest->update([
                'status' => ExpenseRequestStatus::AwaitingExpenseReport,
            ]);

            DocumentEvent::query()->create([
                'subject_type' => $expenseRequest->getMorphClass(),
                'subject_id' => $expenseRequest->getKey(),
                'event_type' => DocumentEventType::ExpenseRequestPaid,
                'actor_user_id' => $actor->id,
                'note' => '-',
                'metadata' => [
                    'payment_id' => $payment->id,
                    'amount_cents' => $amountCents,
                    'payment_method' => $paymentMethod->value,
                    'paid_on' => Carbon::parse($paidOn)->toDateString(),
                ],
            ]);

            $this->budgetLedger->recordSpendIfApplicable($payment, $expenseRequest);

            $payment->refresh();

            return $payment;
        });

        DB::afterCommit(function () use ($expenseRequest, $actor, $payment): void {
            $freshRequest = $expenseRequest->fresh(['user']);
            if ($freshRequest !== null) {
                $this->notifications->notifyRequesterOnPaid($freshRequest, $actor, $payment);
            }
        });

        return $payment;
    }
}
