<?php

namespace App\Notifications\VacationRequests;

use App\Models\VacationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VacationRequestRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public VacationRequest $vacationRequest,
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
        $folio = $this->vacationRequest->folio ?? (string) $this->vacationRequest->id;

        return (new MailMessage)
            ->subject(__('Solicitud de vacaciones rechazada'))
            ->line(__('Tu solicitud de vacaciones fue rechazada.'))
            ->line(__('Folio: :folio', ['folio' => $folio]))
            ->line(__('Motivo: :note', ['note' => $this->note]))
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
            'type' => 'vacation_request.rejected',
            'vacation_request_id' => $this->vacationRequest->id,
            'folio' => $this->vacationRequest->folio,
            'note' => $this->note,
        ];
    }
}
