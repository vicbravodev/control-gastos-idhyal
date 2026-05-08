import {
    AlertTriangle,
    Ban,
    Calculator,
    CheckCircle2,
    Clock,
    FilePen,
    Loader,
    Lock,
    LoaderCircle,
    Receipt,
    ReceiptText,
    Search,
    Send,
    SkipForward,
    XCircle,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

import { cn } from '@/lib/utils';

type StatusVariant =
    | 'info'
    | 'success'
    | 'warning'
    | 'danger'
    | 'muted'
    | 'brand'
    | 'gold';

type StatusSize = 'sm' | 'md' | 'lg';

type StatusEntry = {
    label: string;
    icon: LucideIcon;
    variant: StatusVariant;
    pulse?: boolean;
};

const STATUS_CONFIG: Record<string, StatusEntry> = {
    // --- Lifecycle (ExpenseRequestStatus / VacationRequestStatus) ---
    draft: { label: 'Borrador', icon: FilePen, variant: 'muted' },
    submitted: { label: 'Enviada', icon: Send, variant: 'info' },
    approval_in_progress: {
        label: 'En aprobación',
        icon: LoaderCircle,
        variant: 'info',
    },
    approved: { label: 'Aprobada', icon: CheckCircle2, variant: 'success' },
    pending_payment: {
        label: 'Por pagar',
        icon: Clock,
        variant: 'warning',
        pulse: true,
    },
    paid: { label: 'Pagada', icon: CheckCircle2, variant: 'success' },
    awaiting_expense_report: {
        label: 'Por comprobar',
        icon: ReceiptText,
        variant: 'warning',
        pulse: true,
    },
    expense_report_in_review: {
        label: 'Comprobación en revisión',
        icon: Search,
        variant: 'info',
    },
    expense_report_approved: {
        label: 'Comprobada',
        icon: Receipt,
        variant: 'success',
    },
    expense_report_rejected: {
        label: 'Comprobación rechazada',
        icon: XCircle,
        variant: 'danger',
    },
    accounting_review: {
        label: 'Revisión contable',
        icon: Calculator,
        variant: 'info',
    },
    settlement_pending: {
        label: 'Cuadre pendiente',
        icon: AlertTriangle,
        variant: 'warning',
    },
    closed: { label: 'Cerrada', icon: Lock, variant: 'success' },
    rejected: { label: 'Rechazada', icon: XCircle, variant: 'danger' },
    cancelled: { label: 'Cancelada', icon: Ban, variant: 'muted' },
    expired: { label: 'Vencida', icon: AlertTriangle, variant: 'danger' },
    completed: { label: 'Completada', icon: CheckCircle2, variant: 'success' },

    // --- ApprovalInstanceStatus ---
    pending: { label: 'Pendiente', icon: Clock, variant: 'warning' },
    skipped: { label: 'Omitido', icon: SkipForward, variant: 'muted' },

    // --- ExpenseReportStatus ---
    expense_report_draft: {
        label: 'Comprobación borrador',
        icon: FilePen,
        variant: 'muted',
    },
    expense_report_submitted: {
        label: 'Comprobación enviada',
        icon: Send,
        variant: 'info',
    },

    // --- SettlementStatus ---
    calculated: { label: 'Calculado', icon: Calculator, variant: 'info' },
    pending_user_return: {
        label: 'Pendiente: devolución',
        icon: AlertTriangle,
        variant: 'warning',
    },
    pending_company_payment: {
        label: 'Pendiente: pago complementario',
        icon: AlertTriangle,
        variant: 'warning',
    },
    settled: { label: 'Liquidado', icon: CheckCircle2, variant: 'success' },

    // --- Fallback used when status comes through as an in-flight loader value ---
    in_progress: { label: 'En curso', icon: Loader, variant: 'info' },
};

const VARIANT_CLASSES: Record<StatusVariant, string> = {
    info: 'bg-[var(--info-bg)] text-[var(--info-fg)]',
    success: 'bg-[var(--success-bg)] text-[var(--success-fg)]',
    warning: 'bg-[var(--warning-bg)] text-[var(--warning-fg)]',
    danger: 'bg-[var(--destructive-bg)] text-[var(--destructive-fg)]',
    muted: 'bg-muted text-muted-foreground',
    brand: 'bg-[var(--brand-blue-50)] text-[var(--brand-blue-700)]',
    gold: 'bg-[var(--brand-gold-100)] text-[var(--brand-gold-700)]',
};

const SIZE_CLASSES: Record<StatusSize, string> = {
    sm: 'h-5 px-2 text-[11px] [&>svg]:size-3',
    md: 'h-6 px-2.5 text-xs [&>svg]:size-3.5',
    lg: 'h-7 px-3 text-[13px] [&>svg]:size-4',
};

export function StatusBadge({
    status,
    size = 'md',
    className,
}: {
    status: string;
    size?: StatusSize;
    className?: string;
}) {
    const config = STATUS_CONFIG[status];
    const label = config?.label ?? status;
    const Icon = config?.icon;
    const variant = config?.variant ?? 'muted';

    return (
        <span
            data-status={status}
            className={cn(
                'inline-flex items-center gap-1 rounded-full leading-none font-semibold whitespace-nowrap',
                VARIANT_CLASSES[variant],
                SIZE_CLASSES[size],
                className,
            )}
        >
            {Icon ? (
                <Icon
                    aria-hidden
                    className={cn(
                        'shrink-0',
                        config?.pulse &&
                            'animate-[pulse-soft_1.6s_ease-in-out_infinite]',
                    )}
                />
            ) : null}
            {label}
        </span>
    );
}

export function getStatusLabel(status: string): string {
    return STATUS_CONFIG[status]?.label ?? status;
}

export function getStatusVariant(status: string): StatusVariant {
    return STATUS_CONFIG[status]?.variant ?? 'muted';
}
