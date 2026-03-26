<?php

namespace App\Services\ExpenseRequests;

use App\Enums\DocumentEventType;
use App\Models\DocumentEvent;
use Illuminate\Support\Collection;

class ExpenseRequestDocumentEventTimelinePresenter
{
    /**
     * @param  Collection<int, DocumentEvent>  $events
     * @return list<array{id: int, event_type: string, label: string, actor_name: string, occurred_at: string|null, note: string|null}>
     */
    public function present(Collection $events): array
    {
        return $events
            ->sortBy('created_at')
            ->values()
            ->map(fn (DocumentEvent $event): array => [
                'id' => $event->id,
                'event_type' => $event->event_type->value,
                'label' => $this->label($event->event_type),
                'actor_name' => $event->actor?->name ?? '—',
                'occurred_at' => $event->created_at?->toIso8601String(),
                'note' => $this->noteForDisplay($event->note),
            ])
            ->all();
    }

    private function label(DocumentEventType $type): string
    {
        return match ($type) {
            DocumentEventType::Rejection => 'Rechazo en aprobación',
            DocumentEventType::Cancellation => 'Cancelación de solicitud',
            DocumentEventType::ExpenseRequestSubmitted => 'Envío a aprobación',
            DocumentEventType::ExpenseRequestChainApproved => 'Cadena de aprobación completada',
            DocumentEventType::ExpenseRequestPaid => 'Pago registrado',
            DocumentEventType::ExpenseReportSubmitted => 'Comprobación enviada a revisión',
            DocumentEventType::ExpenseReportApproved => 'Comprobación aprobada',
            DocumentEventType::ExpenseReportRejected => 'Comprobación rechazada',
            DocumentEventType::SettlementLiquidationRecorded => 'Liquidación de balance registrada',
            DocumentEventType::SettlementClosed => 'Balance cerrado',
        };
    }

    private function noteForDisplay(?string $note): ?string
    {
        if ($note === null) {
            return null;
        }

        $trimmed = trim($note);

        if ($trimmed === '' || $trimmed === '-') {
            return null;
        }

        return $note;
    }
}
