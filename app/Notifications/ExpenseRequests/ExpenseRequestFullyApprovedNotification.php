<?php

namespace App\Notifications\ExpenseRequests;

use App\Models\ExpenseRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExpenseRequestFullyApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ExpenseRequest $expenseRequest,
        public User $lastApprover,
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
            ->subject(__('Solicitud de gasto aprobada'))
            ->line(__('Tu solicitud de gasto completó todas las aprobaciones.'))
            ->line(__('Folio: :folio', ['folio' => $folio]))
            ->line(__('Última aprobación registrada por :name.', ['name' => $this->lastApprover->name]))
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
            'type' => 'expense_request.fully_approved',
            'expense_request_id' => $this->expenseRequest->id,
            'folio' => $this->expenseRequest->folio,
            'last_approver_name' => $this->lastApprover->name,
        ];
    }
}
