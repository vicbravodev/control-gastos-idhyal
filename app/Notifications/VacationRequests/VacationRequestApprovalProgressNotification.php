<?php

namespace App\Notifications\VacationRequests;

use App\Models\User;
use App\Models\VacationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VacationRequestApprovalProgressNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array{total_groups: int, remaining_groups: int, completed_groups: int}|null  $progress
     */
    public function __construct(
        public VacationRequest $vacationRequest,
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
        $folio = $this->vacationRequest->folio ?? (string) $this->vacationRequest->id;
        $remaining = $this->progress['remaining_groups'] ?? null;

        $mail = (new MailMessage)
            ->subject(__('Actualización en tu solicitud de vacaciones'))
            ->line(__(':name registró una aprobación en tu solicitud de vacaciones.', ['name' => $this->approver->name]))
            ->line(__('Folio: :folio', ['folio' => $folio]));

        if ($remaining !== null) {
            $mail->line(__('Grupos de aprobación pendientes: :n', ['n' => $remaining]));
        }

        return $mail->action(
            __('Ir al panel'),
            route('dashboard'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'vacation_request.approval_progress',
            'vacation_request_id' => $this->vacationRequest->id,
            'folio' => $this->vacationRequest->folio,
            'approver_name' => $this->approver->name,
            'total_groups' => $this->progress['total_groups'] ?? null,
            'remaining_groups' => $this->progress['remaining_groups'] ?? null,
            'completed_groups' => $this->progress['completed_groups'] ?? null,
        ];
    }
}
