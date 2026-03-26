import { Head, Link } from '@inertiajs/react';
import { Inbox } from 'lucide-react';
import VacationRequestApprovalController from '@/actions/App/Http/Controllers/VacationRequests/VacationRequestApprovalController';
import VacationRequestController from '@/actions/App/Http/Controllers/VacationRequests/VacationRequestController';
import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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

export default function PendingVacationRequestApprovals({
    items,
}: {
    items: PendingItem[];
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Vacaciones por aprobar" />
            <div className="flex flex-col gap-4 p-4 animate-fade-in">
                <Heading
                    title="Solicitudes de vacaciones pendientes"
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
                            <ul className="divide-y rounded-md border">
                                {items.map((row) => (
                                    <li
                                        key={`${row.vacation_request_id}-${row.approval_id}`}
                                        className="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                                    >
                                        <div>
                                            <p className="font-medium">
                                                {row.folio ??
                                                    `#${row.vacation_request_id}`}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {row.requester_name} · Paso{' '}
                                                {row.step_order} (
                                                {row.role_name})
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {row.starts_on ?? '—'} —{' '}
                                                {row.ends_on ?? '—'} ·{' '}
                                                {row.business_days_count} días
                                                hábiles
                                            </p>
                                        </div>
                                        <Button size="sm" asChild>
                                            <Link
                                                href={VacationRequestController.show.url(
                                                    row.vacation_request_id,
                                                )}
                                                prefetch
                                            >
                                                Abrir
                                            </Link>
                                        </Button>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
