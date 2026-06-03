<?php

namespace App\Notifications\ExpenseRequests;

use App\Models\ExpenseRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExpenseRequestOverCapExtensionRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ExpenseRequest $expenseRequest) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->expenseRequest->loadMissing(['expenseConcept', 'user']);

        $folio = $this->expenseRequest->folio ?? (string) $this->expenseRequest->id;
        $approved = (int) ($this->expenseRequest->approved_amount_cents ?? 0);
        $reported = (int) $this->expenseRequest->expenseReports()->sum('reported_amount_cents');

        return (new MailMessage)
            ->subject(__('Re-autorización requerida por comprobación que excede el monto aprobado'))
            ->line(__('La comprobación del folio :folio excede el monto originalmente aprobado y requiere una nueva autorización.', ['folio' => $folio]))
            ->line(__('Solicitante: :name', ['name' => $this->expenseRequest->user?->name ?? '—']))
            ->line(__('Monto aprobado: $:approved', ['approved' => number_format($approved / 100, 2)]))
            ->line(__('Monto comprobado: $:reported', ['reported' => number_format($reported / 100, 2)]))
            ->action(
                __('Revisar solicitud'),
                route('expense-requests.show', $this->expenseRequest),
            );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->expenseRequest->loadMissing(['expenseConcept', 'user']);

        return [
            'type' => 'expense_request.over_cap_extension_requested',
            'expense_request_id' => $this->expenseRequest->id,
            'folio' => $this->expenseRequest->folio,
            'concept' => $this->expenseRequest->conceptLabel(),
            'requester_name' => $this->expenseRequest->user?->name,
            'approved_amount_cents' => (int) ($this->expenseRequest->approved_amount_cents ?? 0),
            'reported_amount_cents' => (int) $this->expenseRequest->expenseReports()->sum('reported_amount_cents'),
        ];
    }
}
