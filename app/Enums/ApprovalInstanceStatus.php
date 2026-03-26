<?php

namespace App\Enums;

/**
 * expense_request_approvals / vacation_request_approvals .status (data-dictionary-stage2).
 */
enum ApprovalInstanceStatus: string
{
    case Pending = 'pending';

    case Approved = 'approved';

    case Rejected = 'rejected';

    case Skipped = 'skipped';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Approved => 'Aprobada',
            self::Rejected => 'Rechazada',
            self::Skipped => 'Omitido',
        };
    }
}
