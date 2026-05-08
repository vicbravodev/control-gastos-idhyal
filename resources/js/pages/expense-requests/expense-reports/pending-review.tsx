import { Head, Link } from '@inertiajs/react';
import { ArrowRight, FileSearch } from 'lucide-react';

import ExpenseReportController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseReportController';
import ExpenseRequestController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestController';
import { EmptyState } from '@/components/empty-state';
import { ListFooter, ListTable, PageHeader } from '@/components/idhyal';
import type { ListFooterPaginator } from '@/components/idhyal';
import { StatusBadge } from '@/components/status-badge';
import { TableToolbar } from '@/components/table-toolbar';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { formatCentsMx } from '@/lib/money';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type ExpenseReportRow = {
    id: number;
    reported_amount_cents: number;
    submitted_at: string | null;
};

type Row = {
    id: number;
    folio: string | null;
    concept_label: string;
    approved_amount_cents: number | null;
    created_at: string | null;
    user: { id: number; name: string };
    expense_report: ExpenseReportRow | null;
};

type Paginator = ListFooterPaginator & {
    data: Row[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    {
        title: 'Comprobaciones por revisar',
        href: ExpenseReportController.pendingReview.url(),
    },
];

const dateFormatter = new Intl.DateTimeFormat('es-MX', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
});

function formatSubmitted(iso: string | null): string {
    if (!iso) {
        return '—';
    }

    try {
        return dateFormatter.format(new Date(iso));
    } catch {
        return '—';
    }
}

export default function ExpenseReportsPendingReview({
    expenseRequests,
    filters,
}: {
    expenseRequests: Paginator;
    filters: Record<string, string>;
}) {
    const isEmpty = expenseRequests.data.length === 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Comprobaciones por revisar" />
            <div className="flex animate-fade-in flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    eyebrow="Gastos"
                    title="Comprobaciones por revisar"
                    subtitle="Solicitudes con comprobación enviada pendiente de revisión contable."
                    actions={
                        <Button variant="outline" asChild>
                            <Link href={ExpenseRequestController.index.url()}>
                                Mis solicitudes
                            </Link>
                        </Button>
                    }
                />

                <div className="rounded-xl border border-border bg-card p-3.5">
                    <TableToolbar
                        currentUrl={ExpenseReportController.pendingReview.url()}
                        filters={filters}
                        searchPlaceholder="Buscar por folio o solicitante…"
                    />
                </div>

                {isEmpty ? (
                    <div className="overflow-hidden rounded-xl border border-border bg-card">
                        <EmptyState
                            icon={FileSearch}
                            title="Sin comprobaciones"
                            description="No hay comprobaciones en revisión en este momento."
                        />
                    </div>
                ) : (
                    <ListTable aria-label="Comprobaciones por revisar">
                        <thead>
                            <tr>
                                <th>Folio</th>
                                <th>Solicitante</th>
                                <th>Concepto</th>
                                <th>Enviada</th>
                                <th className="is-num">Monto comprobado</th>
                                <th>Estado</th>
                                <th aria-label="Acciones" />
                            </tr>
                        </thead>
                        <tbody>
                            {expenseRequests.data.map((row) => (
                                <tr key={row.id}>
                                    <td>
                                        <Link
                                            href={ExpenseRequestController.show.url(
                                                row.id,
                                            )}
                                            className="folio hover:underline"
                                        >
                                            {row.folio ?? `#${row.id}`}
                                        </Link>
                                    </td>
                                    <td>
                                        <span className="font-medium text-foreground">
                                            {row.user.name}
                                        </span>
                                    </td>
                                    <td className="max-w-[300px]">
                                        <span className="line-clamp-1 text-sm text-muted-foreground">
                                            {row.concept_label}
                                        </span>
                                    </td>
                                    <td className="text-muted-foreground">
                                        <span className="t-num">
                                            {formatSubmitted(
                                                row.expense_report
                                                    ?.submitted_at ??
                                                    row.created_at,
                                            )}
                                        </span>
                                    </td>
                                    <td className="is-num">
                                        <span className="t-money">
                                            {row.expense_report
                                                ? formatCentsMx(
                                                      row.expense_report
                                                          .reported_amount_cents,
                                                  )
                                                : '—'}
                                        </span>
                                    </td>
                                    <td>
                                        <StatusBadge status="expense_report_in_review" />
                                    </td>
                                    <td className="is-num">
                                        <Button
                                            size="sm"
                                            variant="brand-soft"
                                            asChild
                                        >
                                            <Link
                                                href={ExpenseRequestController.show.url(
                                                    row.id,
                                                )}
                                            >
                                                Revisar
                                                <ArrowRight />
                                            </Link>
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </ListTable>
                )}

                <ListFooter
                    paginator={expenseRequests}
                    label={
                        expenseRequests.total === 1
                            ? 'comprobación'
                            : 'comprobaciones'
                    }
                />
            </div>
        </AppLayout>
    );
}
