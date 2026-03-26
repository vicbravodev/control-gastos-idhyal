<?php

namespace App\Notifications\ExpenseRequests;

use App\Models\ExpenseRequest;
use App\Models\Settlement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SettlementClosedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ExpenseRequest $expenseRequest,
        public Settlement $settlement,
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
            ->subject(__('Solicitud cerrada'))
            ->line(__('Contabilidad cerró el balance; el ciclo de tu solicitud de gasto quedó finalizado.'))
            ->line(__('Folio: :folio', ['folio' => $folio]))
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
            'type' => 'expense_request.settlement_closed',
            'expense_request_id' => $this->expenseRequest->id,
            'settlement_id' => $this->settlement->id,
            'folio' => $this->expenseRequest->folio,
        ];
    }
}
