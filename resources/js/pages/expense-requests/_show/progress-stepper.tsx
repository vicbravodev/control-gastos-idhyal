import { useState } from 'react';
import {
    CheckCircle2,
    ChevronDown,
    Circle,
    CircleDot,
    XCircle,
} from 'lucide-react';
import { StatusBadge } from '@/components/status-badge';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { cn } from '@/lib/utils';
import { formatCentsMx } from '@/lib/money';
import type { ApprovalProgress, ApprovalRow } from '@/types';
import type { ExpenseReportSummary, PaymentSummary, SettlementSummary } from './types';

type PhaseStatus = 'completed' | 'active' | 'failed' | 'pending';

type Phase = {
    key: string;
    label: string;
    status: PhaseStatus;
    summary: string;
    detail?: React.ReactNode;
};

function resolvePhases({
    requestStatus,
    approvals,
    approvalProgress,
    payment,
    expenseReport,
    settlement,
}: {
    requestStatus: string;
    approvals: ApprovalRow[];
    approvalProgress: ApprovalProgress | null;
    payment: PaymentSummary | null;
    expenseReport: ExpenseReportSummary | null;
    settlement: SettlementSummary | null;
}): Phase[] {
    const phases: Phase[] = [];

    const approvalPhaseStatus = (): PhaseStatus => {
        if (requestStatus === 'rejected') return 'failed';
        if (requestStatus === 'submitted' || requestStatus === 'approval_in_progress')
            return 'active';
        return 'completed';
    };

    const approvalSummary = (): string => {
        if (requestStatus === 'rejected') return 'Solicitud rechazada';
        if (requestStatus === 'submitted') return 'Enviada, esperando aprobación';
        if (requestStatus === 'approval_in_progress') {
            if (approvalProgress) {
                return `${approvalProgress.completed_groups} de ${approvalProgress.total_groups} grupos aprobados`;
            }
            return 'En proceso de aprobación';
        }
        if (approvalProgress) {
            return `${approvalProgress.total_groups} de ${approvalProgress.total_groups} grupos aprobados`;
        }
        return 'Aprobada';
    };

    phases.push({
        key: 'approval',
        label: 'Aprobación',
        status: approvalPhaseStatus(),
        summary: approvalSummary(),
        detail: approvals.length > 0 ? (
            <ul className="space-y-2">
                {approvals.map((a) => (
                    <li key={a.id} className="flex items-center justify-between gap-3 text-sm">
                        <span>
                            Paso {a.step_order} — {a.role.name}
                        </span>
                        <StatusBadge status={a.status} className="text-xs" />
                    </li>
                ))}
            </ul>
        ) : undefined,
    });

    const showPayment = !['submitted', 'approval_in_progress', 'rejected', 'cancelled'].includes(requestStatus);
    if (showPayment) {
        const paymentStatus = (): PhaseStatus => {
            if (payment) return 'completed';
            if (requestStatus === 'pending_payment') return 'active';
            return 'pending';
        };
        phases.push({
            key: 'payment',
            label: 'Pago',
            status: paymentStatus(),
            summary: payment
                ? `${formatCentsMx(payment.amount_cents)} — ${payment.payment_method === 'transfer' ? 'Transferencia' : 'Efectivo'} (${payment.paid_on})`
                : requestStatus === 'pending_payment'
                  ? 'Pendiente de registrar'
                  : 'Pendiente',
            detail: payment ? (
                <dl className="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                    <dt className="text-muted-foreground">Monto</dt>
                    <dd className="text-right tabular-nums font-medium">{formatCentsMx(payment.amount_cents)}</dd>
                    <dt className="text-muted-foreground">Método</dt>
                    <dd className="text-right">{payment.payment_method === 'transfer' ? 'Transferencia' : 'Efectivo'}</dd>
                    <dt className="text-muted-foreground">Fecha</dt>
                    <dd className="text-right">{payment.paid_on}</dd>
                    {payment.transfer_reference && (
                        <>
                            <dt className="text-muted-foreground">Referencia</dt>
                            <dd className="text-right">{payment.transfer_reference}</dd>
                        </>
                    )}
                    <dt className="text-muted-foreground">Registrado por</dt>
                    <dd className="text-right">{payment.recorded_by}</dd>
                </dl>
            ) : undefined,
        });
    }

    const showReport = [
        'awaiting_expense_report', 'expense_report_in_review',
        'expense_report_rejected', 'expense_report_approved',
        'settlement_pending', 'closed',
    ].includes(requestStatus);
    if (showReport) {
        const reportStatus = (): PhaseStatus => {
            if (!expenseReport) return 'active';
            if (expenseReport.status === 'rejected') return 'failed';
            if (['approved'].includes(expenseReport.status)) return 'completed';
            if (['accounting_review', 'draft'].includes(expenseReport.status)) return 'active';
            return 'pending';
        };

        const reportSummary = (): string => {
            if (!expenseReport) return 'Esperando envío por el solicitante';
            if (expenseReport.status === 'draft') return 'Borrador guardado';
            if (expenseReport.status === 'accounting_review') return 'En revisión contable';
            if (expenseReport.status === 'rejected') return 'Rechazada — el solicitante debe reenviar';
            if (expenseReport.status === 'approved')
                return `Aprobada — ${formatCentsMx(expenseReport.reported_amount_cents)}`;
            return expenseReport.status;
        };

        phases.push({
            key: 'report',
            label: 'Comprobación',
            status: reportStatus(),
            summary: reportSummary(),
        });
    }

    const showSettlement = ['settlement_pending', 'closed'].includes(requestStatus);
    if (showSettlement && settlement) {
        const settlementStatus = (): PhaseStatus => {
            if (settlement.status === 'closed') return 'completed';
            if (settlement.status === 'settled') return 'completed';
            return 'active';
        };

        const settlementSummary = (): string => {
            if (settlement.status === 'pending_user_return')
                return `El solicitante debe devolver ${formatCentsMx(Math.abs(settlement.difference_cents))}`;
            if (settlement.status === 'pending_company_payment')
                return `La empresa debe pagar ${formatCentsMx(Math.abs(settlement.difference_cents))} al solicitante`;
            if (settlement.status === 'settled') return 'Liquidación registrada — pendiente de cierre';
            if (settlement.status === 'closed') return 'Cerrado';
            return settlement.status;
        };

        phases.push({
            key: 'settlement',
            label: 'Liquidación',
            status: settlementStatus(),
            summary: settlementSummary(),
            detail: (
                <dl className="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                    <dt className="text-muted-foreground">Base pagada</dt>
                    <dd className="text-right tabular-nums">{formatCentsMx(settlement.basis_amount_cents)}</dd>
                    <dt className="text-muted-foreground">Comprobado</dt>
                    <dd className="text-right tabular-nums">{formatCentsMx(settlement.reported_amount_cents)}</dd>
                    <dt className="text-muted-foreground">Diferencia</dt>
                    <dd className="text-right tabular-nums font-bold">{formatCentsMx(settlement.difference_cents)}</dd>
                </dl>
            ),
        });
    }

    return phases;
}

const statusIcon: Record<PhaseStatus, React.ReactNode> = {
    completed: <CheckCircle2 className="size-5 text-emerald-600 dark:text-emerald-400" />,
    active: <CircleDot className="size-5 text-amber-600 dark:text-amber-400" />,
    failed: <XCircle className="size-5 text-red-600 dark:text-red-400" />,
    pending: <Circle className="size-5 text-muted-foreground/40" />,
};

const lineColor: Record<PhaseStatus, string> = {
    completed: 'bg-emerald-300 dark:bg-emerald-700',
    active: 'bg-amber-300 dark:bg-amber-700',
    failed: 'bg-red-300 dark:bg-red-700',
    pending: 'bg-muted',
};

export default function ProgressStepper({
    requestStatus,
    approvals,
    approvalProgress,
    payment,
    expenseReport,
    settlement,
}: {
    requestStatus: string;
    approvals: ApprovalRow[];
    approvalProgress: ApprovalProgress | null;
    payment: PaymentSummary | null;
    expenseReport: ExpenseReportSummary | null;
    settlement: SettlementSummary | null;
}) {
    const phases = resolvePhases({
        requestStatus,
        approvals,
        approvalProgress,
        payment,
        expenseReport,
        settlement,
    });

    return (
        <div className="space-y-0">
            {phases.map((phase, i) => (
                <StepRow
                    key={phase.key}
                    phase={phase}
                    isLast={i === phases.length - 1}
                />
            ))}
        </div>
    );
}

function StepRow({ phase, isLast }: { phase: Phase; isLast: boolean }) {
    const [open, setOpen] = useState(phase.status === 'active');

    const hasDetail = phase.detail != null;

    return (
        <div className="flex gap-4">
            <div className="flex flex-col items-center">
                <div className="shrink-0 pt-0.5">{statusIcon[phase.status]}</div>
                {!isLast && (
                    <div
                        className={cn('mt-1 w-0.5 flex-1 rounded-full', lineColor[phase.status])}
                    />
                )}
            </div>

            <div className={cn('min-w-0 flex-1', !isLast && 'pb-6')}>
                {hasDetail ? (
                    <Collapsible open={open} onOpenChange={setOpen}>
                        <CollapsibleTrigger className="flex w-full items-center gap-2 text-left">
                            <span className="text-base font-semibold leading-tight">
                                {phase.label}
                            </span>
                            <ChevronDown
                                className={cn(
                                    'size-4 text-muted-foreground transition-transform',
                                    open && 'rotate-180',
                                )}
                            />
                        </CollapsibleTrigger>
                        <p className="mt-0.5 text-sm text-muted-foreground">
                            {phase.summary}
                        </p>
                        <CollapsibleContent className="mt-3 rounded-lg border bg-muted/30 p-4">
                            {phase.detail}
                        </CollapsibleContent>
                    </Collapsible>
                ) : (
                    <>
                        <span className="text-base font-semibold leading-tight">
                            {phase.label}
                        </span>
                        <p className="mt-0.5 text-sm text-muted-foreground">
                            {phase.summary}
                        </p>
                    </>
                )}
            </div>
        </div>
    );
}
