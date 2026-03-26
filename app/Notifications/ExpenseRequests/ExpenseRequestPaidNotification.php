<?php

namespace App\Notifications\ExpenseRequests;

use App\Models\ExpenseRequest;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExpenseRequestPaidNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ExpenseRequest $expenseRequest,
        public Payment $payment,
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
            ->subject(__('Tu solicitud de gasto fue pagada'))
            ->line(__('Contabilidad registró el pago de tu solicitud.'))
            ->line(__('Folio: :folio', ['folio' => $folio]))
            ->line(__('Debes presentar la comprobación de gasto cuando corresponda.'))
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
            'type' => 'expense_request.paid',
            'expense_request_id' => $this->expenseRequest->id,
            'payment_id' => $this->payment->id,
            'folio' => $this->expenseRequest->folio,
            'amount_cents' => $this->payment->amount_cents,
        ];
    }
}
