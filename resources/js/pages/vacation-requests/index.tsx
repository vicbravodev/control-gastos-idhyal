import { Head, Link } from '@inertiajs/react';
import { CalendarDays, Plus } from 'lucide-react';
import VacationRequestController from '@/actions/App/Http/Controllers/VacationRequests/VacationRequestController';
import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import { PaginationNav } from '@/components/pagination-nav';
import { StatusBadge } from '@/components/status-badge';
import { TableToolbar } from '@/components/table-toolbar';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
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

type Paginator = {
    data: ListItem[];
    links: { url: string | null; label: string; active: boolean }[];
    last_page: number;
    current_page: number;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Vacaciones', href: VacationRequestController.index.url() },
];

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
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Solicitudes de vacaciones" />
            <div className="flex flex-col gap-4 p-4 animate-fade-in">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Mis solicitudes de vacaciones"
                        description="Periodos que has registrado en el sistema."
                    />
                    <Button asChild>
                        <Link
                            href={VacationRequestController.create.url()}
                            prefetch
                        >
                            <Plus className="mr-1.5 size-4" />
                            Nueva solicitud
                        </Link>
                    </Button>
                </div>
                <VacationBalanceCard balance={vacationBalance} />
                <Card>
                    <CardHeader>
                        <CardTitle>Listado</CardTitle>
                    </CardHeader>
                    <div className="px-6 pb-4">
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
                    <CardContent>
                        {vacationRequests.data.length === 0 ? (
                            <EmptyState
                                icon={CalendarDays}
                                title="Sin solicitudes"
                                description="No hay solicitudes de vacaciones. Crea una nueva para iniciar el flujo de aprobación."
                                action={
                                    <Button asChild size="sm">
                                        <Link
                                            href={VacationRequestController.create.url()}
                                        >
                                            <Plus className="mr-1.5 size-4" />
                                            Crear solicitud
                                        </Link>
                                    </Button>
                                }
                            />
                        ) : (
                            <>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Folio</TableHead>
                                            <TableHead>Periodo</TableHead>
                                            <TableHead className="text-right">
                                                Días hábiles
                                            </TableHead>
                                            <TableHead>Estado</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {vacationRequests.data.map((row) => (
                                            <TableRow
                                                key={row.id}
                                                className="group cursor-pointer"
                                            >
                                                <TableCell>
                                                    <Link
                                                        href={VacationRequestController.show.url(
                                                            row.id,
                                                        )}
                                                        className="font-medium text-primary underline-offset-4 group-hover:underline"
                                                    >
                                                        {row.folio ??
                                                            `#${row.id}`}
                                                    </Link>
                                                </TableCell>
                                                <TableCell className="text-muted-foreground">
                                                    {row.starts_on ?? '—'} →{' '}
                                                    {row.ends_on ?? '—'}
                                                </TableCell>
                                                <TableCell className="text-right tabular-nums">
                                                    {row.business_days_count}
                                                </TableCell>
                                                <TableCell>
                                                    <StatusBadge
                                                        status={row.status}
                                                    />
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                                <PaginationNav
                                    links={vacationRequests.links}
                                    currentPage={
                                        vacationRequests.current_page
                                    }
                                    lastPage={vacationRequests.last_page}
                                />
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
