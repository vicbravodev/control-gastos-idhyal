import { Head, Link } from '@inertiajs/react';
import { ClipboardList, Plus } from 'lucide-react';
import ExpenseRequestController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestController';
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

type Paginator = {
    data: ListItem[];
    links: { url: string | null; label: string; active: boolean }[];
    last_page: number;
    current_page: number;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    {
        title: 'Solicitudes de gasto',
        href: ExpenseRequestController.index.url(),
    },
];

export default function ExpenseRequestsIndex({
    expenseRequests,
    filters,
    available_statuses,
}: {
    expenseRequests: Paginator;
    filters: Record<string, string>;
    available_statuses: { value: string; label: string }[];
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Solicitudes de gasto" />
            <div className="flex flex-col gap-4 p-4 animate-fade-in">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Mis solicitudes de gasto"
                        description="Solicitudes que has creado en el sistema."
                    />
                    <Button asChild>
                        <Link
                            href={ExpenseRequestController.create.url()}
                            prefetch
                        >
                            <Plus className="mr-1.5 size-4" />
                            Nueva solicitud
                        </Link>
                    </Button>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Listado</CardTitle>
                    </CardHeader>
                    <div className="px-6 pb-4">
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
                    <CardContent>
                        {expenseRequests.data.length === 0 ? (
                            <EmptyState
                                icon={ClipboardList}
                                title="Sin solicitudes"
                                description="No hay solicitudes de gasto. Crea una nueva para iniciar el flujo de aprobación."
                                action={
                                    <Button asChild size="sm">
                                        <Link
                                            href={ExpenseRequestController.create.url()}
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
                                            <TableHead>Concepto</TableHead>
                                            <TableHead>Estado</TableHead>
                                            <TableHead className="text-right">
                                                Monto
                                            </TableHead>
                                            <TableHead className="text-right">
                                                Fecha
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {expenseRequests.data.map((row) => (
                                            <TableRow
                                                key={row.id}
                                                className="group cursor-pointer"
                                            >
                                                <TableCell>
                                                    <Link
                                                        href={ExpenseRequestController.show.url(
                                                            row.id,
                                                        )}
                                                        className="font-medium text-primary underline-offset-4 group-hover:underline"
                                                    >
                                                        {row.folio ??
                                                            `#${row.id}`}
                                                    </Link>
                                                </TableCell>
                                                <TableCell className="max-w-[280px]">
                                                    <span className="line-clamp-2 text-sm">
                                                        <span className="font-medium">
                                                            {
                                                                row.concept_label
                                                            }
                                                        </span>
                                                        {row.concept_description
                                                            ? ` — ${row.concept_description}`
                                                            : ''}
                                                    </span>
                                                </TableCell>
                                                <TableCell>
                                                    <StatusBadge
                                                        status={row.status}
                                                    />
                                                </TableCell>
                                                <TableCell className="text-right font-medium tabular-nums">
                                                    {formatCentsMx(
                                                        row.requested_amount_cents,
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right text-muted-foreground">
                                                    {row.created_at
                                                        ? new Date(
                                                              row.created_at,
                                                          ).toLocaleDateString(
                                                              'es-MX',
                                                              {
                                                                  day: '2-digit',
                                                                  month: 'short',
                                                                  year: 'numeric',
                                                              },
                                                          )
                                                        : '—'}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                                <PaginationNav
                                    links={expenseRequests.links}
                                    currentPage={
                                        expenseRequests.current_page
                                    }
                                    lastPage={expenseRequests.last_page}
                                />
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
