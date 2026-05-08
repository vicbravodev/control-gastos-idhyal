import { Head, Link } from '@inertiajs/react';
import { ChevronRight, ClipboardList, Download, Plus } from 'lucide-react';

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

type ListItem = {
    id: number;
    folio: string | null;
    status: string;
    requested_amount_cents: number;
    concept_label: string;
    concept_description: string | null;
    created_at: string | null;
};

type Paginator = ListFooterPaginator & {
    data: ListItem[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    {
        title: 'Solicitudes de gasto',
        href: ExpenseRequestController.index.url(),
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

export default function ExpenseRequestsIndex({
    expenseRequests,
    filters,
    available_statuses,
}: {
    expenseRequests: Paginator;
    filters: Record<string, string>;
    available_statuses: { value: string; label: string }[];
}) {
    const isEmpty = expenseRequests.data.length === 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Solicitudes de gasto" />
            <div className="flex animate-fade-in flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    eyebrow="Gastos"
                    title="Solicitudes de gasto"
                    subtitle="Todas las solicitudes que has creado o supervisas."
                    actions={
                        <>
                            <Button variant="outline" asChild>
                                <Link
                                    href={ExpenseRequestController.createReimbursement.url()}
                                >
                                    <Download />
                                    Comprobación directa
                                </Link>
                            </Button>
                            <Button asChild>
                                <Link
                                    href={ExpenseRequestController.create.url()}
                                    prefetch
                                >
                                    <Plus />
                                    Nueva solicitud
                                </Link>
                            </Button>
                        </>
                    }
                />

                <div className="rounded-xl border border-border bg-card p-3.5">
                    <TableToolbar
                        currentUrl={ExpenseRequestController.index.url()}
                        filters={filters}
                        searchPlaceholder="Buscar por folio…"
                        filterDefinitions={[
                            {
                                key: 'status',
                                label: 'Estado',
                                options: available_statuses,
                                allLabel: 'Todos los estados',
                            },
                        ]}
                    />
                </div>

                {isEmpty ? (
                    <div className="overflow-hidden rounded-xl border border-border bg-card">
                        <EmptyState
                            icon={ClipboardList}
                            title="Sin solicitudes"
                            description="No hay solicitudes de gasto. Crea una nueva para iniciar el flujo de aprobación."
                            action={
                                <Button asChild size="sm">
                                    <Link
                                        href={ExpenseRequestController.create.url()}
                                    >
                                        <Plus />
                                        Crear solicitud
                                    </Link>
                                </Button>
                            }
                        />
                    </div>
                ) : (
                    <ListTable aria-label="Solicitudes de gasto">
                        <thead>
                            <tr>
                                <th>Folio</th>
                                <th>Concepto</th>
                                <th>Fecha</th>
                                <th className="is-num">Monto</th>
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
                                    <td className="max-w-[360px]">
                                        <div className="line-clamp-2 text-sm">
                                            <span className="font-medium text-foreground">
                                                {row.concept_label}
                                            </span>
                                            {row.concept_description ? (
                                                <span className="text-muted-foreground">
                                                    {' '}
                                                    — {row.concept_description}
                                                </span>
                                            ) : null}
                                        </div>
                                    </td>
                                    <td className="text-muted-foreground">
                                        <span className="t-num">
                                            {formatDate(row.created_at)}
                                        </span>
                                    </td>
                                    <td className="is-num">
                                        <span className="t-money">
                                            {formatCentsMx(
                                                row.requested_amount_cents,
                                            )}
                                        </span>
                                    </td>
                                    <td>
                                        <StatusBadge status={row.status} />
                                    </td>
                                    <td className="is-num text-muted-foreground">
                                        <Link
                                            href={ExpenseRequestController.show.url(
                                                row.id,
                                            )}
                                            className="inline-flex size-6 items-center justify-center rounded-md hover:bg-muted hover:text-foreground"
                                            aria-label={`Abrir ${row.folio ?? `#${row.id}`}`}
                                        >
                                            <ChevronRight className="size-4" />
                                        </Link>
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
