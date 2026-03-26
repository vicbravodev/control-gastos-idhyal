<?php

namespace Tests\Unit;

use App\Enums\DocumentEventType;
use App\Models\DocumentEvent;
use App\Models\User;
use App\Services\ExpenseRequests\ExpenseRequestDocumentEventTimelinePresenter;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ExpenseRequestDocumentEventTimelinePresenterTest extends TestCase
{
    public function test_sorts_by_created_at_and_maps_labels(): void
    {
        $older = new DocumentEvent([
            'event_type' => DocumentEventType::ExpenseRequestSubmitted,
            'note' => '-',
        ]);
        $older->id = 1;
        $older->created_at = Carbon::parse('2026-01-01 10:00:00');
        $older->setRelation('actor', User::factory()->make(['name' => 'Solicitante']));

        $newer = new DocumentEvent([
            'event_type' => DocumentEventType::ExpenseRequestChainApproved,
            'note' => '-',
        ]);
        $newer->id = 2;
        $newer->created_at = Carbon::parse('2026-01-02 11:00:00');
        $newer->setRelation('actor', User::factory()->make(['name' => 'Aprobador']));

        $presenter = new ExpenseRequestDocumentEventTimelinePresenter;
        $rows = $presenter->present(Collection::make([$newer, $older]));

        $this->assertSame('Envío a aprobación', $rows[0]['label']);
        $this->assertSame('Cadena de aprobación completada', $rows[1]['label']);
        $this->assertSame('Solicitante', $rows[0]['actor_name']);
        $this->assertNull($rows[0]['note']);
    }

    public function test_shows_note_when_meaningful(): void
    {
        $event = new DocumentEvent([
            'event_type' => DocumentEventType::Rejection,
            'note' => 'Falta soporte',
        ]);
        $event->id = 3;
        $event->created_at = now();
        $event->setRelation('actor', User::factory()->make(['name' => 'Rev']));

        $presenter = new ExpenseRequestDocumentEventTimelinePresenter;
        $rows = $presenter->present(Collection::make([$event]));

        $this->assertSame('Falta soporte', $rows[0]['note']);
    }
}
