<?php

namespace App\Enums;

/**
 * Persisted expense_report.status values (data-dictionary-stage2 § expense_reports).
 */
enum ExpenseReportStatus: string
{
    case Draft = 'draft';

    case Submitted = 'submitted';

    case AccountingReview = 'accounting_review';

    case Rejected = 'rejected';

    case Approved = 'approved';

    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Submitted => 'Enviada',
            self::AccountingReview => 'En revisión contable',
            self::Rejected => 'Rechazada',
            self::Approved => 'Aprobada',
            self::Cancelled => 'Cancelada',
        };
    }
}
