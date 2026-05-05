<?php

namespace App\Notifications\VacationRequests;

use App\Models\VacationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VacationRequestExpiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public VacationRequest $vacationRequest,
        public int $expirationDays,
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
            ->subject(__('Solicitud de vacaciones expirada'))
            ->line(__('Tu solicitud de vacaciones expiró por inactividad.'))
            ->line(__('Folio: :folio', ['folio' => $folio]))
            ->line(__('No fue aprobada en :days días, así que los días vuelven a tu saldo disponible.', ['days' => $this->expirationDays]))
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
            'type' => 'vacation_request.expired',
            'vacation_request_id' => $this->vacationRequest->id,
            'folio' => $this->vacationRequest->folio,
            'expiration_days' => $this->expirationDays,
        ];
    }
}
