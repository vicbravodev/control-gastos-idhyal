<?php

namespace App\Notifications\ExpenseRequests;

use App\Models\ExpenseRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExpenseRequestSubmittedNotification extends Notification implements ShouldQueue
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
        $this->expenseRequest->loadMissing('expenseConcept');

        $folio = $this->expenseRequest->folio ?? (string) $this->expenseRequest->id;

        return (new MailMessage)
            ->subject(__('Nueva solicitud de gasto pendiente de aprobación'))
            ->line(__('Hay una nueva solicitud de gasto que requiere tu revisión.'))
            ->line(__('Folio: :folio', ['folio' => $folio]))
            ->line(__('Concepto: :concept', ['concept' => $this->expenseRequest->conceptLabel()]))
            ->action(
                __('Ver solicitud'),
                route('expense-requests.show', $this->expenseRequest),
            );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->expenseRequest->loadMissing('expenseConcept');

        return [
            'type' => 'expense_request.submitted_for_approval',
            'expense_request_id' => $this->expenseRequest->id,
            'folio' => $this->expenseRequest->folio,
            'concept' => $this->expenseRequest->conceptLabel(),
            'requester_name' => $this->expenseRequest->user?->name,
        ];
    }
}
