import { Head, Link } from '@inertiajs/react';
import { Inbox } from 'lucide-react';
import ExpenseRequestApprovalController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestApprovalController';
import ExpenseRequestController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestController';
import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
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

type PendingItem = {
    approval_id: number;
    expense_request_id: number;
    folio: string | null;
    concept_label: string;
    requested_amount_cents: number;
    requester_name: string;
    step_order: number;
    role_name: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    {
        title: 'Pendientes de aprobar',
        href: ExpenseRequestApprovalController.pending.url(),
    },
];

export default function PendingExpenseRequestApprovals({
    items,
}: {
    items: PendingItem[];
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pendientes de aprobar" />
            <div className="flex flex-col gap-4 p-4 animate-fade-in">
                <Heading
                    title="Solicitudes pendientes de tu aprobación"
                    description="Solo se listan pasos activos según la política de aprobación."
                />
                <Card>
                    <CardHeader>
                        <CardTitle>Bandeja</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {items.length === 0 ? (
                            <EmptyState
                                icon={Inbox}
                                title="Sin aprobaciones pendientes"
                                description="No tienes aprobaciones activas en este momento."
                            />
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Folio</TableHead>
                                        <TableHead>Solicitante</TableHead>
                                        <TableHead>Concepto</TableHead>
                                        <TableHead>Paso</TableHead>
                                        <TableHead className="text-right">
                                            Monto
                                        </TableHead>
                                        <TableHead />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {items.map((row) => (
                                        <TableRow
                                            key={`${row.expense_request_id}-${row.approval_id}`}
                                        >
                                            <TableCell className="font-medium">
                                                {row.folio ??
                                                    `#${row.expense_request_id}`}
                                            </TableCell>
                                            <TableCell>
                                                {row.requester_name}
                                            </TableCell>
                                            <TableCell className="max-w-[200px]">
                                                <span className="line-clamp-1">
                                                    {row.concept_label}
                                                </span>
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                Paso {row.step_order} (
                                                {row.role_name})
                                            </TableCell>
                                            <TableCell className="text-right font-medium tabular-nums">
                                                {formatCentsMx(
                                                    row.requested_amount_cents,
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button size="sm" asChild>
                                                    <Link
                                                        href={ExpenseRequestController.show.url(
                                                            row.expense_request_id,
                                                        )}
                                                        prefetch
                                                    >
                                                        Abrir
                                                    </Link>
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
