import { Head, Link } from '@inertiajs/react';
import { Wallet } from 'lucide-react';
import ExpenseRequestController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestController';
import ExpenseRequestPaymentController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestPaymentController';
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

type Row = {
    id: number;
    folio: string | null;
    concept_label: string;
    requested_amount_cents: number;
    approved_amount_cents: number | null;
    created_at: string | null;
    user: { id: number; name: string };
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
        title: 'Pagos pendientes',
        href: ExpenseRequestPaymentController.pending.url(),
    },
];

export default function ExpenseRequestPaymentsPending({
    expenseRequests,
    filters,
}: {
    expenseRequests: Paginator;
    filters: Record<string, string>;
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pagos pendientes" />
            <div className="flex flex-col gap-4 p-4 animate-fade-in">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Pagos pendientes"
                        description="Solicitudes aprobadas listas para registrar el pago."
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
                            currentUrl={ExpenseRequestPaymentController.pending.url()}
                            filters={filters}
                            searchPlaceholder="Buscar por folio o solicitante…"
                        />
                    </div>
                    <CardContent>
                        {expenseRequests.data.length === 0 ? (
                            <EmptyState
                                icon={Wallet}
                                title="Sin pagos pendientes"
                                description="No hay solicitudes pendientes de pago en este momento."
                            />
                        ) : (
                            <>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Folio</TableHead>
                                            <TableHead>Solicitante</TableHead>
                                            <TableHead>Concepto</TableHead>
                                            <TableHead>Estado</TableHead>
                                            <TableHead className="text-right">
                                                A pagar
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
                                                <TableCell className="max-w-[200px]">
                                                    <span className="line-clamp-1">
                                                        {row.concept_label}
                                                    </span>
                                                </TableCell>
                                                <TableCell>
                                                    <StatusBadge status="pending_payment" />
                                                </TableCell>
                                                <TableCell className="text-right font-medium tabular-nums">
                                                    {formatCentsMx(
                                                        row.approved_amount_cents ??
                                                            row.requested_amount_cents,
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <Button size="sm" asChild>
                                                        <Link
                                                            href={ExpenseRequestController.show.url(
                                                                row.id,
                                                            )}
                                                        >
                                                            Registrar pago
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
