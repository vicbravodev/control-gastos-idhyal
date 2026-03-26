<?php

namespace App\Enums;

/**
 * document_events.event_type (data-dictionary-stage2); extend as new audit types appear.
 */
enum DocumentEventType: string
{
    case Rejection = 'rejection';

    case Cancellation = 'cancellation';

    /** Solicitud registrada y enviada al flujo de aprobación (acuse / auditoría). */
    case ExpenseRequestSubmitted = 'expense_request_submitted';

    /** Último paso de la cadena AND/OR completado; solicitud pasa a pendiente de pago. */
    case ExpenseRequestChainApproved = 'expense_request_chain_approved';

    /** Pago registrado por contabilidad con evidencia (transición a comprobación). */
    case ExpenseRequestPaid = 'expense_request_paid';

    /** Comprobación enviada a revisión de contabilidad (PDF/XML). */
    case ExpenseReportSubmitted = 'expense_report_submitted';

    case ExpenseReportApproved = 'expense_report_approved';

    case ExpenseReportRejected = 'expense_report_rejected';

    /** Evidencia de liquidación (devolución o pago complementario) registrada por contabilidad. */
    case SettlementLiquidationRecorded = 'settlement_liquidation_recorded';

    /** Cierre contable del settlement y del ciclo de la solicitud. */
    case SettlementClosed = 'settlement_closed';
}
