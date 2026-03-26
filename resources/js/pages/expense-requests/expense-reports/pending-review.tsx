import { Head, Link } from '@inertiajs/react';
import { FileSearch } from 'lucide-react';
import ExpenseReportController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseReportController';
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

type Paginator = {
    data: Row[];
    links: { url: string | null; label: string; active: boolean }[];
    last_page: number;
    current_page: number;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    {
        title: 'Comprobaciones por revisar',
        href: ExpenseReportController.pendingReview.url(),
    },
];

export default function ExpenseReportsPendingReview({
    expenseRequests,
    filters,
}: {
    expenseRequests: Paginator;
    filters: Record<string, string>;
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Comprobaciones por revisar" />
            <div className="flex flex-col gap-4 p-4 animate-fade-in">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Comprobaciones por revisar"
                        description="Solicitudes con comprobación enviada pendiente de revisión contable."
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
                            currentUrl={ExpenseReportController.pendingReview.url()}
                            filters={filters}
                            searchPlaceholder="Buscar por folio o solicitante…"
                        />
                    </div>
                    <CardContent>
                        {expenseRequests.data.length === 0 ? (
                            <EmptyState
                                icon={FileSearch}
                                title="Sin comprobaciones"
                                description="No hay comprobaciones en revisión en este momento."
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
                                                Monto comprobado
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
                                                    <StatusBadge status="expense_report_in_review" />
                                                </TableCell>
                                                <TableCell className="text-right font-medium tabular-nums">
                                                    {row.expense_report
                                                        ? formatCentsMx(
                                                              row
                                                                  .expense_report
                                                                  .reported_amount_cents,
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
                                                            Revisar
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
