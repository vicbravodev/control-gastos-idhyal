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

export type CfdiConcepto = {
    descripcion: string | null;
    cantidad: number | null;
    unidad: string | null;
    valor_unitario: number | null;
    importe: number | null;
    clave_prod_serv: string | null;
};

export type CfdiTraslado = {
    impuesto: string;
    impuesto_label: string;
    tipo_factor: string;
    tasa_o_cuota: number | null;
    base_cents: number;
    importe_cents: number;
    nivel: 'document' | 'concept';
    concepto_index: number | null;
};

export type CfdiRetencion = {
    impuesto: string;
    impuesto_label: string;
    tipo_factor: string | null;
    tasa_o_cuota: number | null;
    base_cents: number | null;
    importe_cents: number;
    nivel: 'document' | 'concept';
    concepto_index: number | null;
};

export type CfdiImpuestoLocal = {
    clave: string;
    tipo: 'traslado' | 'retencion';
    tasa: number | null;
    importe_cents: number;
};

export type CfdiSummary = {
    uuid: string;
    emisor_rfc: string | null;
    emisor_nombre: string | null;
    emisor_regimen_fiscal: string | null;
    receptor_rfc: string | null;
    receptor_nombre: string | null;
    fecha: string | null;
    serie: string | null;
    folio: string | null;
    forma_pago: string | null;
    metodo_pago: string | null;
    uso_cfdi: string | null;
    conceptos: CfdiConcepto[];
    has_hidrocarburos_complement: boolean;
    traslados: CfdiTraslado[];
    retenciones: CfdiRetencion[];
    impuestos_locales: CfdiImpuestoLocal[];
};

export type ExpenseReportSummary = {
    id: number;
    status: string;
    label: string | null;
    reported_amount_cents: number;
    document_type: 'factura' | 'recibo';
    document_type_label: string;
    submitted_at: string | null;
    reviewer_name: string | null;
    reviewed_at: string | null;
    has_pdf_and_xml: boolean;
    verification_pdf_attachment_id?: number | null;
    verification_xml_attachment_id?: number | null;
    cfdi: CfdiSummary | null;
};

export type BalanceSummary = {
    approved_cents: number;
    reported_cents: number;
    remaining_cents: number;
    over_cap: boolean;
    over_cap_pending_extra_approval: boolean;
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
    expense_reports: ExpenseReportSummary[];
    balance: BalanceSummary;
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
