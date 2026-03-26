<?php

namespace App\Notifications\VacationRequests;

use App\Models\VacationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VacationRequestSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public VacationRequest $vacationRequest) {}

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
        $starts = $this->vacationRequest->starts_on?->toDateString() ?? '—';
        $ends = $this->vacationRequest->ends_on?->toDateString() ?? '—';

        return (new MailMessage)
            ->subject(__('Nueva solicitud de vacaciones pendiente de aprobación'))
            ->line(__('Hay una nueva solicitud de vacaciones que requiere tu revisión.'))
            ->line(__('Folio: :folio', ['folio' => $folio]))
            ->line(__('Periodo: :start — :end', ['start' => $starts, 'end' => $ends]))
            ->line(__('Solicitante: :name', ['name' => $this->vacationRequest->user?->name ?? '—']))
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
            'type' => 'vacation_request.submitted_for_approval',
            'vacation_request_id' => $this->vacationRequest->id,
            'folio' => $this->vacationRequest->folio,
            'starts_on' => $this->vacationRequest->starts_on?->toDateString(),
            'ends_on' => $this->vacationRequest->ends_on?->toDateString(),
            'requester_name' => $this->vacationRequest->user?->name,
        ];
    }
}
