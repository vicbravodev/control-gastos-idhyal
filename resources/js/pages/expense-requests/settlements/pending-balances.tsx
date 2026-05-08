import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Scale } from 'lucide-react';

import ExpenseRequestController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestController';
import ExpenseRequestSettlementController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestSettlementController';
import { EmptyState } from '@/components/empty-state';
import { ListFooter, ListTable, PageHeader } from '@/components/idhyal';
import type { ListFooterPaginator } from '@/components/idhyal';
import { StatusBadge } from '@/components/status-badge';
import { TableToolbar } from '@/components/table-toolbar';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { formatCentsMx } from '@/lib/money';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type SettlementRow = {
    status: string;
    difference_cents: number;
    basis_amount_cents: number;
    reported_amount_cents: number;
};

type Row = {
    id: number;
    folio: string | null;
    concept_label: string;
    created_at: string | null;
    user: { id: number; name: string };
    settlement: SettlementRow | null;
};

type Paginator = ListFooterPaginator & {
    data: Row[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    {
        title: 'Balances pendientes',
        href: ExpenseRequestSettlementController.pendingBalances.url(),
    },
];

function differenceTone(diffCents: number | undefined): string {
    if (diffCents === undefined) {
        return '';
    }

    if (diffCents > 0) {
        return 'text-[var(--success-fg)]';
    }

    if (diffCents < 0) {
        return 'text-[var(--destructive-fg)]';
    }

    return 'text-muted-foreground';
}

export default function SettlementsPendingBalances({
    expenseRequests,
    filters,
}: {
    expenseRequests: Paginator;
    filters: Record<string, string>;
}) {
    const isEmpty = expenseRequests.data.length === 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Balances pendientes" />
            <div className="flex animate-fade-in flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    eyebrow="Gastos"
                    title="Balances pendientes"
                    subtitle="Solicitudes con balance tras comprobación pendiente de liquidar."
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
                        currentUrl={ExpenseRequestSettlementController.pendingBalances.url()}
                        filters={filters}
                        searchPlaceholder="Buscar por folio o solicitante…"
                    />
                </div>

                {isEmpty ? (
                    <div className="overflow-hidden rounded-xl border border-border bg-card">
                        <EmptyState
                            icon={Scale}
                            title="Sin balances pendientes"
                            description="No hay balances pendientes de liquidar."
                        />
                    </div>
                ) : (
                    <ListTable aria-label="Balances pendientes">
                        <thead>
                            <tr>
                                <th>Folio</th>
                                <th>Solicitante</th>
                                <th>Estado</th>
                                <th className="is-num">Base pagada</th>
                                <th className="is-num">Comprobado</th>
                                <th className="is-num">Diferencia</th>
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
                                    <td>
                                        {row.settlement ? (
                                            <StatusBadge
                                                status={row.settlement.status}
                                            />
                                        ) : (
                                            '—'
                                        )}
                                    </td>
                                    <td className="is-num text-muted-foreground">
                                        <span className="t-num">
                                            {row.settlement
                                                ? formatCentsMx(
                                                      row.settlement
                                                          .basis_amount_cents,
                                                  )
                                                : '—'}
                                        </span>
                                    </td>
                                    <td className="is-num text-muted-foreground">
                                        <span className="t-num">
                                            {row.settlement
                                                ? formatCentsMx(
                                                      row.settlement
                                                          .reported_amount_cents,
                                                  )
                                                : '—'}
                                        </span>
                                    </td>
                                    <td className="is-num">
                                        <span
                                            className={cn(
                                                't-money',
                                                differenceTone(
                                                    row.settlement
                                                        ?.difference_cents,
                                                ),
                                            )}
                                        >
                                            {row.settlement
                                                ? formatCentsMx(
                                                      row.settlement
                                                          .difference_cents,
                                                  )
                                                : '—'}
                                        </span>
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
                                                Ver detalle
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
                    label={expenseRequests.total === 1 ? 'balance' : 'balances'}
                />
            </div>
        </AppLayout>
    );
}
