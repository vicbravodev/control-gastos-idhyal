<?php

namespace App\Notifications\ExpenseRequests;

use App\Models\ExpenseRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExpenseRequestApprovalProgressNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array{total_groups: int, remaining_groups: int, completed_groups: int}|null  $progress
     */
    public function __construct(
        public ExpenseRequest $expenseRequest,
        public User $approver,
        public ?array $progress,
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
        $remaining = $this->progress['remaining_groups'] ?? null;

        $mail = (new MailMessage)
            ->subject(__('Actualización en tu solicitud de gasto'))
            ->line(__(':name registró una aprobación en tu solicitud.', ['name' => $this->approver->name]))
            ->line(__('Folio: :folio', ['folio' => $folio]));

        if ($remaining !== null) {
            $mail->line(__('Grupos de aprobación pendientes: :n', ['n' => $remaining]));
        }

        return $mail->action(
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
            'type' => 'expense_request.approval_progress',
            'expense_request_id' => $this->expenseRequest->id,
            'folio' => $this->expenseRequest->folio,
            'approver_name' => $this->approver->name,
            'total_groups' => $this->progress['total_groups'] ?? null,
            'remaining_groups' => $this->progress['remaining_groups'] ?? null,
            'completed_groups' => $this->progress['completed_groups'] ?? null,
        ];
    }
}
