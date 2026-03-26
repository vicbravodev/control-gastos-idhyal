<?php

namespace App\Notifications\ExpenseRequests;

use App\Models\ExpenseRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExpenseReportSubmittedForReviewNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ExpenseRequest $expenseRequest,
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
            ->subject(__('Comprobación de gasto por revisar'))
            ->line(__('Un solicitante envió la comprobación (PDF/XML) para revisión.'))
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
            'type' => 'expense_request.expense_report_submitted',
            'expense_request_id' => $this->expenseRequest->id,
            'folio' => $this->expenseRequest->folio,
        ];
    }
}
