import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, Download } from 'lucide-react';

import VacationRequestApprovalController from '@/actions/App/Http/Controllers/VacationRequests/VacationRequestApprovalController';
import VacationRequestController from '@/actions/App/Http/Controllers/VacationRequests/VacationRequestController';
import ActiveApprovalCard from '@/components/active-approval-card';
import ApprovalsCard from '@/components/approvals-card';
import { DetailRow } from '@/components/idhyal';
import { StatusBadge } from '@/components/status-badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { ApprovalProgress, ApprovalRow, BreadcrumbItem } from '@/types';

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

function formatLong(iso: string | null): string {
    if (!iso) {
        return '—';
    }

    try {
        return new Intl.DateTimeFormat('es-MX', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        }).format(new Date(iso + 'T12:00:00'));
    } catch {
        return iso;
    }
}

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
            <div className="mx-auto flex w-full max-w-5xl animate-fade-in flex-col gap-6 p-4 pb-16 sm:p-6">
                {flash?.status && (
                    <Alert className="border-[var(--success-bg)] bg-[var(--success-bg)] text-[var(--success-fg)]">
                        <CheckCircle2 className="size-4" />
                        <AlertTitle>Listo</AlertTitle>
                        <AlertDescription>{flash.status}</AlertDescription>
                    </Alert>
                )}

                {/* Hero strip */}
                <section className="rounded-xl border border-border bg-card p-5 sm:p-6">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div className="min-w-0">
                            <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                <span className="t-folio font-semibold text-[var(--brand-blue-700)]">
                                    {vacationRequest.folio ??
                                        `#${vacationRequest.id}`}
                                </span>
                                <span>·</span>
                                <span>Solicitud de vacaciones</span>
                                <span>·</span>
                                <span>
                                    Solicitante: {vacationRequest.user.name}
                                </span>
                            </div>
                            <h1 className="mt-2 text-2xl font-bold tracking-[-0.02em]">
                                {formatLong(vacationRequest.starts_on)}
                                <span className="mx-2 text-muted-foreground">
                                    →
                                </span>
                                {formatLong(vacationRequest.ends_on)}
                            </h1>
                            <div className="mt-3 flex flex-wrap items-center gap-4">
                                <StatusBadge
                                    status={vacationRequest.status}
                                    size="lg"
                                />
                                <div className="t-num text-2xl font-bold tracking-[-0.02em]">
                                    {vacationRequest.business_days_count}
                                    <span className="ml-1 text-base font-medium text-muted-foreground">
                                        días hábiles
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            {canDownloadFinalApprovalReceipt && (
                                <Button variant="outline" asChild>
                                    <a
                                        href={VacationRequestController.downloadFinalApprovalReceipt.url(
                                            vacationRequest.id,
                                        )}
                                    >
                                        <Download />
                                        Recibo PDF
                                    </a>
                                </Button>
                            )}
                            <Button variant="outline" asChild>
                                <Link
                                    href={VacationRequestController.index.url()}
                                >
                                    <ArrowLeft />
                                    Volver
                                </Link>
                            </Button>
                        </div>
                    </div>
                </section>

                {/* Datos */}
                <section className="overflow-hidden rounded-xl border border-border bg-card">
                    <header className="border-b border-border px-5 py-3.5">
                        <h2 className="text-sm font-semibold">Periodo</h2>
                    </header>
                    <div className="px-5 py-2">
                        <DetailRow label="Inicio">
                            {formatLong(vacationRequest.starts_on)}
                        </DetailRow>
                        <DetailRow label="Fin">
                            {formatLong(vacationRequest.ends_on)}
                        </DetailRow>
                        <DetailRow label="Días hábiles">
                            <span className="t-num font-semibold">
                                {vacationRequest.business_days_count}
                            </span>
                        </DetailRow>
                    </div>
                </section>

                {/* Cadena de aprobación (existing card kept) */}
                <ApprovalsCard
                    approvals={vacationRequest.approvals}
                    progress={vacationRequest.approval_progress}
                />

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
