import { Head, Link } from '@inertiajs/react';
import { CalendarDays, ChevronRight, Plus } from 'lucide-react';

import VacationRequestController from '@/actions/App/Http/Controllers/VacationRequests/VacationRequestController';
import { EmptyState } from '@/components/empty-state';
import { ListFooter, ListTable, PageHeader } from '@/components/idhyal';
import type { ListFooterPaginator } from '@/components/idhyal';
import { StatusBadge } from '@/components/status-badge';
import { TableToolbar } from '@/components/table-toolbar';
import { Button } from '@/components/ui/button';
import { VacationBalanceCard } from '@/components/vacation-balance-card';
import type { VacationBalancePayload } from '@/components/vacation-balance-card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type ListItem = {
    id: number;
    folio: string | null;
    status: string;
    starts_on: string | null;
    ends_on: string | null;
    business_days_count: number;
    created_at: string | null;
};

type Paginator = ListFooterPaginator & {
    data: ListItem[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Vacaciones', href: VacationRequestController.index.url() },
];

const dateFormatter = new Intl.DateTimeFormat('es-MX', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
});

function formatLong(iso: string | null): string {
    if (!iso) {
        return '—';
    }

    try {
        return dateFormatter.format(new Date(iso + 'T12:00:00'));
    } catch {
        return iso;
    }
}

export default function VacationRequestsIndex({
    vacationRequests,
    vacationBalance,
    filters,
    available_statuses,
}: {
    vacationRequests: Paginator;
    vacationBalance: VacationBalancePayload | null;
    filters: Record<string, string>;
    available_statuses: { value: string; label: string }[];
}) {
    const isEmpty = vacationRequests.data.length === 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Solicitudes de vacaciones" />
            <div className="flex animate-fade-in flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    eyebrow="Vacaciones"
                    title="Mis solicitudes de vacaciones"
                    subtitle="Periodos que has registrado en el sistema."
                    actions={
                        <Button asChild>
                            <Link
                                href={VacationRequestController.create.url()}
                                prefetch
                            >
                                <Plus />
                                Nueva solicitud
                            </Link>
                        </Button>
                    }
                />

                <VacationBalanceCard balance={vacationBalance} />

                <div className="rounded-xl border border-border bg-card p-3.5">
                    <TableToolbar
                        currentUrl={VacationRequestController.index.url()}
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
                            icon={CalendarDays}
                            title="Sin solicitudes"
                            description="No hay solicitudes de vacaciones. Crea una nueva para iniciar el flujo de aprobación."
                            action={
                                <Button asChild size="sm">
                                    <Link
                                        href={VacationRequestController.create.url()}
                                    >
                                        <Plus />
                                        Crear solicitud
                                    </Link>
                                </Button>
                            }
                        />
                    </div>
                ) : (
                    <ListTable aria-label="Mis solicitudes de vacaciones">
                        <thead>
                            <tr>
                                <th>Folio</th>
                                <th>Periodo</th>
                                <th className="is-num">Días hábiles</th>
                                <th>Estado</th>
                                <th aria-label="Acciones" />
                            </tr>
                        </thead>
                        <tbody>
                            {vacationRequests.data.map((row) => (
                                <tr key={row.id}>
                                    <td>
                                        <Link
                                            href={VacationRequestController.show.url(
                                                row.id,
                                            )}
                                            className="folio hover:underline"
                                        >
                                            {row.folio ?? `#${row.id}`}
                                        </Link>
                                    </td>
                                    <td className="text-muted-foreground">
                                        <span className="t-num">
                                            {formatLong(row.starts_on)} →{' '}
                                            {formatLong(row.ends_on)}
                                        </span>
                                    </td>
                                    <td className="is-num">
                                        <span className="t-num font-semibold text-foreground">
                                            {row.business_days_count}
                                        </span>
                                    </td>
                                    <td>
                                        <StatusBadge status={row.status} />
                                    </td>
                                    <td className="is-num text-muted-foreground">
                                        <Link
                                            href={VacationRequestController.show.url(
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
                    paginator={vacationRequests}
                    label={
                        vacationRequests.total === 1
                            ? 'solicitud'
                            : 'solicitudes'
                    }
                />
            </div>
        </AppLayout>
    );
}
