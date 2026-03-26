<?php

namespace App\Notifications\ExpenseRequests;

use App\Models\ExpenseRequest;
use App\Models\Settlement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExpenseReportApprovedNotification extends Notification implements ShouldQueue
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
        $mail = (new MailMessage)
            ->subject(__('Comprobación aprobada'))
            ->line(__('Contabilidad aprobó tu comprobación de gasto.'))
            ->line(__('Folio: :folio', ['folio' => $folio]))
            ->line(__('Diferencia (base pagada menos comprobado): :diff centavos', [
                'diff' => (string) $this->settlement->difference_cents,
            ]))
            ->action(
                __('Ver solicitud'),
                route('expense-requests.show', $this->expenseRequest),
            );

        return $mail;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'expense_request.expense_report_approved',
            'expense_request_id' => $this->expenseRequest->id,
            'settlement_id' => $this->settlement->id,
            'difference_cents' => $this->settlement->difference_cents,
            'folio' => $this->expenseRequest->folio,
        ];
    }
}
