<?php

namespace App\Services\VacationRequests;

use App\Models\VacationRequest;

/**
 * Folios legibles para solicitudes de vacaciones: VAC-{year}-{id}.
 */
final class VacationRequestFolioGenerator
{
    public function assign(VacationRequest $vacationRequest): void
    {
        $vacationRequest->forceFill([
            'folio' => sprintf('VAC-%d-%d', now()->year, $vacationRequest->getKey()),
        ])->save();
    }
}
