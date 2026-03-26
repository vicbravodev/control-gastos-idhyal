import type { ApprovalProgress, ApprovalRow } from '@/types';

export type PaymentSummary = {
    id: number;
    amount_cents: number;
    payment_method: string;
    paid_on: string;
    transfer_reference: string | null;
    recorded_by: string;
    evidence_original_filename: string | null | undefined;
    evidence_attachment_id?: number | null;
};

export type ExpenseReportSummary = {
    id: number;
    status: string;
    reported_amount_cents: number;
    submitted_at: string | null;
    has_pdf_and_xml: boolean;
    verification_pdf_attachment_id?: number | null;
    verification_xml_attachment_id?: number | null;
};

export type SettlementSummary = {
    id: number;
    status: string;
    basis_amount_cents: number;
    reported_amount_cents: number;
    difference_cents: number;
    liquidation_evidence_original_filename?: string | null;
    liquidation_evidence_attachment_id?: number | null;
};

export type SubmissionAttachmentRow = {
    id: number;
    original_filename: string;
    mime_type: string | null;
    size_bytes: number | null;
    can_download: boolean;
    can_delete: boolean;
};

export type DocumentTimelineRow = {
    id: number;
    event_type: string;
    label: string;
    actor_name: string;
    occurred_at: string | null;
    note: string | null;
};

export type Detail = {
    id: number;
    folio: string | null;
    status: string;
    requested_amount_cents: number;
    approved_amount_cents: number | null;
    concept_label: string;
    concept_description: string | null;
    delivery_method: string;
    created_at: string | null;
    user: { id: number; name: string };
    approvals: ApprovalRow[];
    approval_progress: ApprovalProgress | null;
    payment: PaymentSummary | null;
    expense_report: ExpenseReportSummary | null;
    settlement: SettlementSummary | null;
    submission_attachments: SubmissionAttachmentRow[];
    document_timeline: DocumentTimelineRow[];
};

export type PaymentFormState = {
    amount_cents: number;
    payment_method: string;
    paid_on: string;
    transfer_reference: string;
    evidence: File | null;
};

export type ReportFileFormState = {
    reported_amount_cents: number;
    pdf: File | null;
    xml: File | null;
};

export type SettlementLiquidationFormState = {
    evidence: File | null;
};

export function formatDate(iso: string | null): string {
    if (!iso) {
        return '—';
    }
    try {
        return new Date(iso).toLocaleString('es-MX', {
            dateStyle: 'short',
            timeStyle: 'short',
        });
    } catch {
        return iso;
    }
}

export function settlementStatusLabel(status: string): string {
    switch (status) {
        case 'pending_user_return':
            return 'Pendiente: el solicitante debe devolver la diferencia';
        case 'pending_company_payment':
            return 'Pendiente: la empresa debe pagar la diferencia al solicitante';
        case 'settled':
            return 'Liquidación registrada';
        case 'closed':
            return 'Cerrado';
        default:
            return status;
    }
}

export function DataRow({
    label,
    children,
}: {
    label: string;
    children: React.ReactNode;
}) {
    return (
        <div className="flex items-baseline justify-between gap-4 py-2">
            <span className="shrink-0 text-sm text-muted-foreground">
                {label}
            </span>
            <span className="text-right text-sm font-medium">{children}</span>
        </div>
    );
}
