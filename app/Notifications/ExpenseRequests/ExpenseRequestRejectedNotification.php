<?php

namespace App\Notifications\ExpenseRequests;

use App\Models\ExpenseRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExpenseRequestRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ExpenseRequest $expenseRequest,
        public string $note,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $folio = $this->expenseRequest->folio ?? (string) $this->expenseRequest->id;

        return (new MailMessage)
            ->subject(__('Solicitud de gasto rechazada'))
            ->line(__('Tu solicitud de gasto fue rechazada.'))
            ->line(__('Folio: :folio', ['folio' => $folio]))
            ->line(__('Motivo: :note', ['note' => $this->note]))
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
        return [
            'type' => 'expense_request.rejected',
            'expense_request_id' => $this->expenseRequest->id,
            'folio' => $this->expenseRequest->folio,
            'note' => $this->note,
        ];
    }
}
