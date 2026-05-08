import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Inbox } from 'lucide-react';

import VacationRequestApprovalController from '@/actions/App/Http/Controllers/VacationRequests/VacationRequestApprovalController';
import VacationRequestController from '@/actions/App/Http/Controllers/VacationRequests/VacationRequestController';
import { EmptyState } from '@/components/empty-state';
import { ListTable, PageHeader } from '@/components/idhyal';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type PendingItem = {
    approval_id: number;
    vacation_request_id: number;
    folio: string | null;
    starts_on: string | null;
    ends_on: string | null;
    business_days_count: number;
    requester_name: string;
    step_order: number;
    role_name: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    {
        title: 'Vacaciones por aprobar',
        href: VacationRequestApprovalController.pending.url(),
    },
];

const dateFormatter = new Intl.DateTimeFormat('es-MX', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
});

function formatRange(starts: string | null, ends: string | null): string {
    if (!starts || !ends) {
        return `${starts ?? '—'} → ${ends ?? '—'}`;
    }

    try {
        const fmt = (d: string) =>
            dateFormatter.format(new Date(d + 'T12:00:00'));

        return `${fmt(starts)} → ${fmt(ends)}`;
    } catch {
        return `${starts} → ${ends}`;
    }
}

export default function PendingVacationRequestApprovals({
    items,
}: {
    items: PendingItem[];
}) {
    const isEmpty = items.length === 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Vacaciones por aprobar" />
            <div className="flex animate-fade-in flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    eyebrow="Vacaciones"
                    title="Solicitudes de vacaciones pendientes"
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
                    <ListTable aria-label="Vacaciones pendientes de aprobar">
                        <thead>
                            <tr>
                                <th>Folio</th>
                                <th>Solicitante</th>
                                <th>Periodo</th>
                                <th className="is-num">Días</th>
                                <th>Paso</th>
                                <th aria-label="Acciones" />
                            </tr>
                        </thead>
                        <tbody>
                            {items.map((row) => (
                                <tr
                                    key={`${row.vacation_request_id}-${row.approval_id}`}
                                >
                                    <td>
                                        <Link
                                            href={VacationRequestController.show.url(
                                                row.vacation_request_id,
                                            )}
                                            className="folio hover:underline"
                                        >
                                            {row.folio ??
                                                `#${row.vacation_request_id}`}
                                        </Link>
                                    </td>
                                    <td>
                                        <span className="font-medium text-foreground">
                                            {row.requester_name}
                                        </span>
                                    </td>
                                    <td className="text-muted-foreground">
                                        <span className="t-num">
                                            {formatRange(
                                                row.starts_on,
                                                row.ends_on,
                                            )}
                                        </span>
                                    </td>
                                    <td className="is-num">
                                        <span className="t-num font-semibold text-foreground">
                                            {row.business_days_count}
                                        </span>
                                    </td>
                                    <td className="text-muted-foreground">
                                        Paso {row.step_order}
                                        <div className="text-xs text-[var(--subtle-fg)]">
                                            {row.role_name}
                                        </div>
                                    </td>
                                    <td className="is-num">
                                        <Button
                                            size="sm"
                                            variant="brand-soft"
                                            asChild
                                        >
                                            <Link
                                                href={VacationRequestController.show.url(
                                                    row.vacation_request_id,
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
