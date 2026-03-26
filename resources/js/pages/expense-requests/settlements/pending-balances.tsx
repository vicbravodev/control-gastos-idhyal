import { Head, Link } from '@inertiajs/react';
import { Scale } from 'lucide-react';
import ExpenseRequestController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestController';
import ExpenseRequestSettlementController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestSettlementController';
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

type Paginator = {
    data: Row[];
    links: { url: string | null; label: string; active: boolean }[];
    last_page: number;
    current_page: number;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    {
        title: 'Balances pendientes',
        href: ExpenseRequestSettlementController.pendingBalances.url(),
    },
];

export default function SettlementsPendingBalances({
    expenseRequests,
    filters,
}: {
    expenseRequests: Paginator;
    filters: Record<string, string>;
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Balances pendientes" />
            <div className="flex flex-col gap-4 p-4 animate-fade-in">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Balances pendientes"
                        description="Solicitudes con balance tras comprobación pendiente de liquidar."
                    />
                    <Button variant="outline" asChild>
                        <Link href={ExpenseRequestController.index.url()}>
                            Mis solicitudes
                        </Link>
                    </Button>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Bandeja</CardTitle>
                    </CardHeader>
                    <div className="px-6 pb-4">
                        <TableToolbar
                            currentUrl={ExpenseRequestSettlementController.pendingBalances.url()}
                            filters={filters}
                            searchPlaceholder="Buscar por folio o solicitante…"
                        />
                    </div>
                    <CardContent>
                        {expenseRequests.data.length === 0 ? (
                            <EmptyState
                                icon={Scale}
                                title="Sin balances pendientes"
                                description="No hay balances pendientes de liquidar."
                            />
                        ) : (
                            <>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Folio</TableHead>
                                            <TableHead>Solicitante</TableHead>
                                            <TableHead>Estado</TableHead>
                                            <TableHead className="text-right">
                                                Base pagada
                                            </TableHead>
                                            <TableHead className="text-right">
                                                Comprobado
                                            </TableHead>
                                            <TableHead className="text-right">
                                                Diferencia
                                            </TableHead>
                                            <TableHead />
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {expenseRequests.data.map((row) => (
                                            <TableRow key={row.id}>
                                                <TableCell className="font-medium">
                                                    {row.folio ??
                                                        `#${row.id}`}
                                                </TableCell>
                                                <TableCell>
                                                    {row.user.name}
                                                </TableCell>
                                                <TableCell>
                                                    {row.settlement && (
                                                        <StatusBadge
                                                            status={
                                                                row.settlement
                                                                    .status
                                                            }
                                                        />
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right tabular-nums">
                                                    {row.settlement
                                                        ? formatCentsMx(
                                                              row.settlement
                                                                  .basis_amount_cents,
                                                          )
                                                        : '—'}
                                                </TableCell>
                                                <TableCell className="text-right tabular-nums">
                                                    {row.settlement
                                                        ? formatCentsMx(
                                                              row.settlement
                                                                  .reported_amount_cents,
                                                          )
                                                        : '—'}
                                                </TableCell>
                                                <TableCell className="text-right font-medium tabular-nums">
                                                    {row.settlement
                                                        ? formatCentsMx(
                                                              row.settlement
                                                                  .difference_cents,
                                                          )
                                                        : '—'}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <Button size="sm" asChild>
                                                        <Link
                                                            href={ExpenseRequestController.show.url(
                                                                row.id,
                                                            )}
                                                        >
                                                            Ver detalle
                                                        </Link>
                                                    </Button>
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
