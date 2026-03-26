<?php

namespace App\Notifications\VacationRequests;

use App\Models\User;
use App\Models\VacationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VacationRequestFullyApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public VacationRequest $vacationRequest,
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
        $folio = $this->vacationRequest->folio ?? (string) $this->vacationRequest->id;

        return (new MailMessage)
            ->subject(__('Solicitud de vacaciones aprobada'))
            ->line(__('Tu solicitud de vacaciones completó todas las aprobaciones.'))
            ->line(__('Folio: :folio', ['folio' => $folio]))
            ->line(__('Última aprobación registrada por :name.', ['name' => $this->lastApprover->name]))
            ->action(
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
            'type' => 'vacation_request.fully_approved',
            'vacation_request_id' => $this->vacationRequest->id,
            'folio' => $this->vacationRequest->folio,
            'last_approver_name' => $this->lastApprover->name,
        ];
    }
}
