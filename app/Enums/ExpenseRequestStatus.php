<?php

namespace App\Enums;

/**
 * Persisted expense_request.status values (data-dictionary-stage2 § expense_requests).
 */
enum ExpenseRequestStatus: string
{
    case Submitted = 'submitted';

    case ApprovalInProgress = 'approval_in_progress';

    case Rejected = 'rejected';

    case Cancelled = 'cancelled';

    case Approved = 'approved';

    case PendingPayment = 'pending_payment';

    case Paid = 'paid';

    case AwaitingExpenseReport = 'awaiting_expense_report';

    case ExpenseReportInReview = 'expense_report_in_review';

    case ExpenseReportRejected = 'expense_report_rejected';

    case ExpenseReportApproved = 'expense_report_approved';

    case SettlementPending = 'settlement_pending';

    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Enviada',
            self::ApprovalInProgress => 'En aprobación',
            self::Rejected => 'Rechazada',
            self::Cancelled => 'Cancelada',
            self::Approved => 'Aprobada',
            self::PendingPayment => 'Pendiente de pago',
            self::Paid => 'Pagada',
            self::AwaitingExpenseReport => 'Esperando comprobación',
            self::ExpenseReportInReview => 'Comprobación en revisión',
            self::ExpenseReportRejected => 'Comprobación rechazada',
            self::ExpenseReportApproved => 'Comprobación aprobada',
            self::SettlementPending => 'Liquidación pendiente',
            self::Closed => 'Cerrada',
        };
    }
}
