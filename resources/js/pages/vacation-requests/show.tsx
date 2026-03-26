import { Head, Link, usePage } from '@inertiajs/react';
import VacationRequestApprovalController from '@/actions/App/Http/Controllers/VacationRequests/VacationRequestApprovalController';
import VacationRequestController from '@/actions/App/Http/Controllers/VacationRequests/VacationRequestController';
import ActiveApprovalCard from '@/components/active-approval-card';
import ApprovalsCard from '@/components/approvals-card';
import Heading from '@/components/heading';
import { StatusBadge } from '@/components/status-badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type {
    ApprovalProgress,
    ApprovalRow,
    BreadcrumbItem,
} from '@/types';

type Detail = {
    id: number;
    folio: string | null;
    status: string;
    starts_on: string | null;
    ends_on: string | null;
    business_days_count: number;
    created_at: string | null;
    user: { id: number; name: string };
    approvals: ApprovalRow[];
    approval_progress: ApprovalProgress | null;
};

const breadcrumbs = (id: number): BreadcrumbItem[] => [
    { title: 'Dashboard', href: dashboard() },
    {
        title: 'Vacaciones',
        href: VacationRequestController.index.url(),
    },
    {
        title: 'Detalle',
        href: VacationRequestController.show.url(id),
    },
];

export default function VacationRequestsShow({
    vacationRequest,
    canDownloadFinalApprovalReceipt,
    activeApprovalId,
}: {
    vacationRequest: Detail;
    canDownloadFinalApprovalReceipt: boolean;
    activeApprovalId: number | null;
}) {
    const { flash } = usePage<{ flash?: { status?: string } }>().props;

    const activeApproval = vacationRequest.approvals.find(
        (a) => a.id === activeApprovalId,
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs(vacationRequest.id)}>
            <Head
                title={
                    vacationRequest.folio
                        ? `Vacaciones ${vacationRequest.folio}`
                        : 'Solicitud de vacaciones'
                }
            />
            <div className="flex flex-col gap-4 p-4">
                {flash?.status && (
                    <Alert>
                        <AlertTitle>Listo</AlertTitle>
                        <AlertDescription>{flash.status}</AlertDescription>
                    </Alert>
                )}
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={
                            vacationRequest.folio ??
                            `Solicitud #${vacationRequest.id}`
                        }
                        description={`Solicitante: ${vacationRequest.user.name}`}
                    />
                    <div className="flex flex-wrap gap-2">
                        {canDownloadFinalApprovalReceipt && (
                            <Button variant="outline" asChild>
                                <a
                                    href={VacationRequestController.downloadFinalApprovalReceipt.url(
                                        vacationRequest.id,
                                    )}
                                >
                                    Recibo de aprobación (PDF)
                                </a>
                            </Button>
                        )}
                        <Button variant="outline" asChild>
                            <Link href={VacationRequestController.index.url()}>
                                Volver al listado
                            </Link>
                        </Button>
                    </div>
                </div>
                <div className="grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Periodo</CardTitle>
                            <CardDescription>
                                Estado:{' '}
                                <StatusBadge
                                    status={vacationRequest.status}
                                    className="ml-1"
                                />
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            <p>
                                <span className="text-muted-foreground">
                                    Inicio:{' '}
                                </span>
                                {vacationRequest.starts_on ?? '—'}
                            </p>
                            <p>
                                <span className="text-muted-foreground">
                                    Fin:{' '}
                                </span>
                                {vacationRequest.ends_on ?? '—'}
                            </p>
                            <p>
                                <span className="text-muted-foreground">
                                    Días hábiles:{' '}
                                </span>
                                {vacationRequest.business_days_count}
                            </p>
                        </CardContent>
                    </Card>
                    <ApprovalsCard
                        approvals={vacationRequest.approvals}
                        progress={vacationRequest.approval_progress}
                    />
                </div>
                {activeApproval && (
                    <ActiveApprovalCard
                        approval={activeApproval}
                        approveAction={VacationRequestApprovalController.approve.form.post(
                            {
                                vacation_request: vacationRequest.id,
                                approval: activeApproval.id,
                            },
                        )}
                        rejectAction={VacationRequestApprovalController.reject.form.post(
                            {
                                vacation_request: vacationRequest.id,
                                approval: activeApproval.id,
                            },
                        )}
                    />
                )}
            </div>
        </AppLayout>
    );
}
