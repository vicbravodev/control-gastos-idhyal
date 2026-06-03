<?php

namespace App\Enums;

/**
 * Persisted vacation_request.status values (data-dictionary-stage2 § vacation_requests).
 */
enum VacationRequestStatus: string
{
    case Submitted = 'submitted';

    case ApprovalInProgress = 'approval_in_progress';

    case Rejected = 'rejected';

    case Cancelled = 'cancelled';

    case Approved = 'approved';

    case Completed = 'completed';

    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Enviada',
            self::ApprovalInProgress => 'En aprobación',
            self::Rejected => 'Rechazada',
            self::Cancelled => 'Cancelada',
            self::Approved => 'Aprobada',
            self::Completed => 'Completada',
            self::Expired => 'Expirada por inactividad',
        };
    }
}
