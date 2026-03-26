import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

const STATUS_CONFIG: Record<
    string,
    { label: string; className: string }
> = {
    // --- ExpenseRequestStatus / VacationRequestStatus ---
    submitted: {
        label: 'Enviada',
        className: 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-950 dark:text-blue-300 dark:border-blue-800',
    },
    approval_in_progress: {
        label: 'En aprobación',
        className: 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-950 dark:text-amber-300 dark:border-amber-800',
    },
    approved: {
        label: 'Aprobada',
        className: 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-950 dark:text-emerald-300 dark:border-emerald-800',
    },
    rejected: {
        label: 'Rechazada',
        className: 'bg-red-100 text-red-800 border-red-200 dark:bg-red-950 dark:text-red-300 dark:border-red-800',
    },
    cancelled: {
        label: 'Cancelada',
        className: 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-900 dark:text-gray-400 dark:border-gray-700',
    },
    pending_payment: {
        label: 'Pendiente de pago',
        className: 'bg-orange-100 text-orange-800 border-orange-200 dark:bg-orange-950 dark:text-orange-300 dark:border-orange-800',
    },
    paid: {
        label: 'Pagada',
        className: 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-950 dark:text-emerald-300 dark:border-emerald-800',
    },
    awaiting_expense_report: {
        label: 'Esperando comprobación',
        className: 'bg-indigo-100 text-indigo-800 border-indigo-200 dark:bg-indigo-950 dark:text-indigo-300 dark:border-indigo-800',
    },
    settlement_pending: {
        label: 'Liquidación pendiente',
        className: 'bg-orange-100 text-orange-800 border-orange-200 dark:bg-orange-950 dark:text-orange-300 dark:border-orange-800',
    },
    completed: {
        label: 'Completada',
        className: 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-950 dark:text-emerald-300 dark:border-emerald-800',
    },
    closed: {
        label: 'Cerrado',
        className: 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-900 dark:text-gray-400 dark:border-gray-700',
    },

    // --- ApprovalInstanceStatus ---
    pending: {
        label: 'Pendiente',
        className: 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-950 dark:text-amber-300 dark:border-amber-800',
    },
    skipped: {
        label: 'Omitido',
        className: 'bg-gray-100 text-gray-500 border-gray-200 dark:bg-gray-900 dark:text-gray-400 dark:border-gray-700',
    },

    // --- ExpenseReportStatus (direct values from expense_reports.status) ---
    draft: {
        label: 'Borrador',
        className: 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-900 dark:text-gray-400 dark:border-gray-700',
    },
    accounting_review: {
        label: 'En revisión contable',
        className: 'bg-violet-100 text-violet-800 border-violet-200 dark:bg-violet-950 dark:text-violet-300 dark:border-violet-800',
    },

    // --- ExpenseRequestStatus (expense report lifecycle on the request) ---
    expense_report_draft: {
        label: 'Comprobación borrador',
        className: 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-900 dark:text-slate-400 dark:border-slate-700',
    },
    expense_report_submitted: {
        label: 'Comprobación enviada',
        className: 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-950 dark:text-blue-300 dark:border-blue-800',
    },
    expense_report_in_review: {
        label: 'Comprobación en revisión',
        className: 'bg-violet-100 text-violet-800 border-violet-200 dark:bg-violet-950 dark:text-violet-300 dark:border-violet-800',
    },
    expense_report_approved: {
        label: 'Comprobación aprobada',
        className: 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-950 dark:text-emerald-300 dark:border-emerald-800',
    },
    expense_report_rejected: {
        label: 'Comprobación rechazada',
        className: 'bg-red-100 text-red-800 border-red-200 dark:bg-red-950 dark:text-red-300 dark:border-red-800',
    },

    // --- SettlementStatus ---
    calculated: {
        label: 'Calculado',
        className: 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-950 dark:text-blue-300 dark:border-blue-800',
    },
    pending_user_return: {
        label: 'Pendiente: devolución',
        className: 'bg-orange-100 text-orange-800 border-orange-200 dark:bg-orange-950 dark:text-orange-300 dark:border-orange-800',
    },
    pending_company_payment: {
        label: 'Pendiente: pago complementario',
        className: 'bg-orange-100 text-orange-800 border-orange-200 dark:bg-orange-950 dark:text-orange-300 dark:border-orange-800',
    },
    settled: {
        label: 'Liquidado',
        className: 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-950 dark:text-emerald-300 dark:border-emerald-800',
    },
};

export function StatusBadge({
    status,
    className,
}: {
    status: string;
    className?: string;
}) {
    const config = STATUS_CONFIG[status];
    const label = config?.label ?? status;
    const colorClass = config?.className ?? 'bg-secondary text-secondary-foreground';

    return (
        <Badge
            variant="outline"
            className={cn(
                'border font-medium',
                colorClass,
                className,
            )}
        >
            {label}
        </Badge>
    );
}

export function getStatusLabel(status: string): string {
    return STATUS_CONFIG[status]?.label ?? status;
}
