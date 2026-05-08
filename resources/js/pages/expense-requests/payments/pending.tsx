import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Wallet } from 'lucide-react';

import ExpenseRequestController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestController';
import ExpenseRequestPaymentController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestPaymentController';
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

type Row = {
    id: number;
    folio: string | null;
    concept_label: string;
    requested_amount_cents: number;
    approved_amount_cents: number | null;
    created_at: string | null;
    user: { id: number; name: string };
};

type Paginator = ListFooterPaginator & {
    data: Row[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    {
        title: 'Pagos pendientes',
        href: ExpenseRequestPaymentController.pending.url(),
    },
];

const dateFormatter = new Intl.DateTimeFormat('es-MX', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
});

function formatDate(iso: string | null): string {
    if (!iso) {
        return '—';
    }

    try {
        return dateFormatter.format(new Date(iso));
    } catch {
        return '—';
    }
}

export default function ExpenseRequestPaymentsPending({
    expenseRequests,
    filters,
}: {
    expenseRequests: Paginator;
    filters: Record<string, string>;
}) {
    const isEmpty = expenseRequests.data.length === 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pagos pendientes" />
            <div className="flex animate-fade-in flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    eyebrow="Gastos"
                    title="Pagos pendientes"
                    subtitle="Solicitudes aprobadas listas para registrar el pago."
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
                        currentUrl={ExpenseRequestPaymentController.pending.url()}
                        filters={filters}
                        searchPlaceholder="Buscar por folio o solicitante…"
                    />
                </div>

                {isEmpty ? (
                    <div className="overflow-hidden rounded-xl border border-border bg-card">
                        <EmptyState
                            icon={Wallet}
                            title="Sin pagos pendientes"
                            description="No hay solicitudes pendientes de pago en este momento."
                        />
                    </div>
                ) : (
                    <ListTable aria-label="Pagos pendientes">
                        <thead>
                            <tr>
                                <th>Folio</th>
                                <th>Solicitante</th>
                                <th>Concepto</th>
                                <th>Aprobada</th>
                                <th className="is-num">A pagar</th>
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
                                    <td className="max-w-[280px]">
                                        <span className="line-clamp-1 text-sm text-muted-foreground">
                                            {row.concept_label}
                                        </span>
                                    </td>
                                    <td className="text-muted-foreground">
                                        <span className="t-num">
                                            {formatDate(row.created_at)}
                                        </span>
                                    </td>
                                    <td className="is-num">
                                        <span className="t-money">
                                            {formatCentsMx(
                                                row.approved_amount_cents ??
                                                    row.requested_amount_cents,
                                            )}
                                        </span>
                                    </td>
                                    <td>
                                        <StatusBadge status="pending_payment" />
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
                                                Registrar pago
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
                            ? 'solicitud'
                            : 'solicitudes'
                    }
                />
            </div>
        </AppLayout>
    );
}
