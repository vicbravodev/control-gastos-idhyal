import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Inbox } from 'lucide-react';

import ExpenseRequestApprovalController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestApprovalController';
import ExpenseRequestController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestController';
import { EmptyState } from '@/components/empty-state';
import { ListTable, PageHeader } from '@/components/idhyal';
import { Button } from '@/components/ui/button';
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
    const isEmpty = items.length === 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pendientes de aprobar" />
            <div className="flex animate-fade-in flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    eyebrow="Gastos"
                    title="Pendientes de tu aprobación"
                    subtitle="Solo se listan pasos activos según la política de aprobación."
                />

                {isEmpty ? (
                    <div className="overflow-hidden rounded-xl border border-border bg-card">
                        <EmptyState
                            icon={Inbox}
                            title="Sin aprobaciones pendientes"
                            description="No tienes aprobaciones activas en este momento."
                        />
                    </div>
                ) : (
                    <ListTable aria-label="Pendientes de aprobar">
                        <thead>
                            <tr>
                                <th>Folio</th>
                                <th>Solicitante</th>
                                <th>Concepto</th>
                                <th>Paso</th>
                                <th className="is-num">Monto</th>
                                <th aria-label="Acciones" />
                            </tr>
                        </thead>
                        <tbody>
                            {items.map((row) => (
                                <tr
                                    key={`${row.expense_request_id}-${row.approval_id}`}
                                >
                                    <td>
                                        <Link
                                            href={ExpenseRequestController.show.url(
                                                row.expense_request_id,
                                            )}
                                            className="folio hover:underline"
                                        >
                                            {row.folio ??
                                                `#${row.expense_request_id}`}
                                        </Link>
                                    </td>
                                    <td>
                                        <span className="font-medium text-foreground">
                                            {row.requester_name}
                                        </span>
                                    </td>
                                    <td className="max-w-[280px]">
                                        <span className="line-clamp-1 text-sm text-muted-foreground">
                                            {row.concept_label}
                                        </span>
                                    </td>
                                    <td className="text-muted-foreground">
                                        Paso {row.step_order}
                                        <div className="text-xs text-[var(--subtle-fg)]">
                                            {row.role_name}
                                        </div>
                                    </td>
                                    <td className="is-num">
                                        <span className="t-money">
                                            {formatCentsMx(
                                                row.requested_amount_cents,
                                            )}
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
                                                    row.expense_request_id,
                                                )}
                                                prefetch
                                            >
                                                Abrir
                                                <ArrowRight />
                                            </Link>
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </ListTable>
                )}
            </div>
        </AppLayout>
    );
}
