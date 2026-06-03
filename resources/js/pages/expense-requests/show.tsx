import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    CheckCircle2,
    EllipsisVertical,
    GitBranch,
    Info,
    MessageSquare,
    Paperclip,
    Pencil,
    XCircle,
} from 'lucide-react';
import { useState } from 'react';

import ExpenseRequestController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestController';
import { ApprovalTimeline, DetailRow } from '@/components/idhyal';
import type { ApprovalTimelineStep } from '@/components/idhyal';
import InputError from '@/components/input-error';
import { StatusBadge } from '@/components/status-badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { formatCentsMx } from '@/lib/money';
import { dashboard } from '@/routes';
import type { ApprovalRow, BreadcrumbItem } from '@/types';
import ActionRequiredCard from './_show/action-required-card';
import DocumentTimelineCard from './_show/document-timeline-card';
import DocumentsSection from './_show/documents-section';
import ExpenseReportsSection from './_show/expense-reports-section';
import PaymentSummaryCard from './_show/payment-summary-card';
import type { BalanceSummary, Detail } from './_show/types';

const breadcrumbs = (id: number): BreadcrumbItem[] => [
    { title: 'Dashboard', href: dashboard() },
    {
        title: 'Solicitudes de gasto',
        href: ExpenseRequestController.index.url(),
    },
    {
        title: 'Detalle',
        href: ExpenseRequestController.show.url(id),
    },
];

const longDateFormatter = new Intl.DateTimeFormat('es-MX', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

const longDateTimeFormatter = new Intl.DateTimeFormat('es-MX', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
});

function formatLongDate(iso: string | null): string {
    if (!iso) {
        return '—';
    }

    try {
        return longDateFormatter.format(new Date(iso));
    } catch {
        return '—';
    }
}

function formatLongDateTime(iso: string | null): string {
    if (!iso) {
        return '—';
    }

    try {
        return longDateTimeFormatter.format(new Date(iso));
    } catch {
        return '—';
    }
}

function buildApprovalSteps(
    detail: Detail,
    activeApprovalId: number | null,
): ApprovalTimelineStep[] {
    const steps: ApprovalTimelineStep[] = [
        {
            actor: detail.user.name,
            role: 'Envió la solicitud',
            status: 'done',
            timestamp: detail.created_at
                ? formatLongDateTime(detail.created_at)
                : undefined,
        },
    ];

    for (const approval of detail.approvals) {
        const isCurrent = activeApprovalId === approval.id;
        const status =
            approval.status === 'approved'
                ? ('done' as const)
                : approval.status === 'rejected'
                  ? ('rejected' as const)
                  : isCurrent
                    ? ('current' as const)
                    : ('pending' as const);

        const actor = approval.approver.name?.trim()
            ? approval.approver.name
            : approval.approver.type_label;
        const role =
            actor === approval.approver.type_label
                ? undefined
                : approval.approver.type_label;

        let timestamp: string | undefined;

        if (status === 'done' || status === 'rejected') {
            timestamp = approval.acted_at
                ? formatLongDateTime(approval.acted_at)
                : undefined;
        } else if (status === 'current') {
            timestamp = 'Pendiente · esperando decisión';
        } else {
            timestamp = 'Pendiente';
        }

        steps.push({
            actor,
            role,
            status,
            timestamp,
            note: approval.note ?? undefined,
        });
    }

    return steps;
}

function describeAmount(detail: Detail): {
    primaryCents: number;
    showApproved: boolean;
} {
    if (detail.approved_amount_cents !== null) {
        return {
            primaryCents: detail.approved_amount_cents,
            showApproved: true,
        };
    }

    return { primaryCents: detail.requested_amount_cents, showApproved: false };
}

export default function ExpenseRequestsShow({
    expenseRequest,
    canUpdate,
    canDownloadSubmissionReceipt,
    canDownloadFinalApprovalReceipt,
    canDownloadPaymentReceipt,
    canDownloadPaymentEvidence,
    canDownloadSettlementLiquidationReceipt,
    canDownloadExpenseReportVerificationReceipt,
    canDownloadExpenseReportVerificationPdf,
    canDownloadExpenseReportVerificationXml,
    canRecordPayment,
    canSubmitExpenseReport,
    canReviewExpenseReport,
    canRecordSettlementLiquidation,
    canCloseSettlement,
    canCancel,
    canAddSubmissionAttachments,
    canRebuildWorkflow = false,
    activeApprovalId,
}: {
    expenseRequest: Detail;
    canUpdate: boolean;
    canDownloadSubmissionReceipt: boolean;
    canDownloadFinalApprovalReceipt: boolean;
    canDownloadPaymentReceipt: boolean;
    canDownloadPaymentEvidence: boolean;
    canDownloadSettlementLiquidationReceipt: boolean;
    canDownloadExpenseReportVerificationReceipt: boolean;
    canDownloadExpenseReportVerificationPdf: boolean;
    canDownloadExpenseReportVerificationXml: boolean;
    canRecordPayment: boolean;
    canSubmitExpenseReport: boolean;
    canReviewExpenseReport: boolean;
    canRecordSettlementLiquidation: boolean;
    canCloseSettlement: boolean;
    canCancel: boolean;
    canAddSubmissionAttachments: boolean;
    canRebuildWorkflow?: boolean;
    activeApprovalId: number | null;
}) {
    const { flash } = usePage<{ flash?: { status?: string } }>().props;

    const activeApproval: ApprovalRow | undefined =
        expenseRequest.approvals.find((a) => a.id === activeApprovalId);

    const hasAction =
        !!activeApproval ||
        canRecordPayment ||
        canSubmitExpenseReport ||
        canRecordSettlementLiquidation ||
        canCloseSettlement;

    const deliveryLabel =
        expenseRequest.delivery_method === 'cash'
            ? 'Efectivo'
            : expenseRequest.delivery_method === 'transfer'
              ? 'Transferencia'
              : expenseRequest.delivery_method;

    const { primaryCents, showApproved } = describeAmount(expenseRequest);
    const approvalSteps = buildApprovalSteps(expenseRequest, activeApprovalId);

    const downloadButtons = [
        {
            show: canDownloadSubmissionReceipt,
            href: ExpenseRequestController.downloadSubmissionReceipt.url(
                expenseRequest.id,
            ),
            label: 'Acuse envío',
        },
        {
            show: canDownloadFinalApprovalReceipt,
            href: ExpenseRequestController.downloadFinalApprovalReceipt.url(
                expenseRequest.id,
            ),
            label: 'Recibo aprobación',
        },
        {
            show: canDownloadPaymentReceipt,
            href: ExpenseRequestController.downloadPaymentReceipt.url(
                expenseRequest.id,
            ),
            label: 'Recibo pago',
        },
        {
            show: canDownloadSettlementLiquidationReceipt,
            href: ExpenseRequestController.downloadSettlementLiquidationReceipt.url(
                expenseRequest.id,
            ),
            label: 'Recibo liquidación',
        },
        {
            show: canDownloadExpenseReportVerificationReceipt,
            href: ExpenseRequestController.downloadExpenseReportVerificationReceipt.url(
                expenseRequest.id,
            ),
            label: 'Acuse comprobación',
        },
        {
            show:
                canDownloadPaymentEvidence &&
                expenseRequest.payment?.evidence_attachment_id != null,
            href:
                expenseRequest.payment?.evidence_attachment_id != null
                    ? ExpenseRequestController.downloadPaymentEvidence.url({
                          expense_request: expenseRequest.id,
                          attachment:
                              expenseRequest.payment.evidence_attachment_id,
                      })
                    : '#',
            label: 'Evidencia pago',
        },
    ].filter((b) => b.show);

    return (
        <AppLayout breadcrumbs={breadcrumbs(expenseRequest.id)}>
            <Head
                title={
                    expenseRequest.folio
                        ? `Solicitud ${expenseRequest.folio}`
                        : 'Solicitud de gasto'
                }
            />
            <div className="flex animate-fade-in flex-col gap-5 p-4 pb-16 sm:p-6">
                {flash?.status && (
                    <Alert className="border-[var(--success-bg)] bg-[var(--success-bg)] text-[var(--success-fg)]">
                        <CheckCircle2 className="size-4" />
                        <AlertTitle>Listo</AlertTitle>
                        <AlertDescription>{flash.status}</AlertDescription>
                    </Alert>
                )}

                {/* ── Hero ─────────────────────────────────── */}
                <section className="rounded-xl border border-border bg-card p-5 sm:p-6">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div className="min-w-0">
                            <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                <span className="t-folio font-semibold text-[var(--brand-blue-700)]">
                                    {expenseRequest.folio ??
                                        `#${expenseRequest.id}`}
                                </span>
                                <span aria-hidden>·</span>
                                <span>Solicitud de gasto</span>
                                <span aria-hidden>·</span>
                                <span>
                                    Solicitante: {expenseRequest.user.name}
                                </span>
                                {expenseRequest.created_at ? (
                                    <>
                                        <span aria-hidden>·</span>
                                        <span>
                                            Creada{' '}
                                            {formatLongDate(
                                                expenseRequest.created_at,
                                            )}
                                        </span>
                                    </>
                                ) : null}
                            </div>
                            <h1 className="mt-2 text-2xl font-bold tracking-[-0.02em]">
                                {expenseRequest.concept_label}
                            </h1>
                            <div className="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2">
                                <StatusBadge
                                    status={expenseRequest.status}
                                    size="lg"
                                />
                                <div className="t-num text-2xl font-bold tracking-[-0.02em]">
                                    {formatCentsMx(primaryCents)}
                                </div>
                                {showApproved ? (
                                    <span className="text-sm text-muted-foreground">
                                        · solicitado{' '}
                                        <span className="t-num font-semibold text-foreground">
                                            {formatCentsMx(
                                                expenseRequest.requested_amount_cents,
                                            )}
                                        </span>
                                    </span>
                                ) : null}
                            </div>
                        </div>
                        <HeaderActions
                            expenseRequestId={expenseRequest.id}
                            canUpdate={canUpdate}
                            canCancel={canCancel}
                        />
                    </div>
                </section>

                <div className="grid gap-5 lg:grid-cols-[2fr_1fr]">
                    {/* ── Left column ──────────────────────── */}
                    <div className="flex flex-col gap-5">
                        {hasAction && (
                            <ActionRequiredCard
                                expenseRequest={expenseRequest}
                                canRecordPayment={canRecordPayment}
                                canSubmitExpenseReport={canSubmitExpenseReport}
                                canRecordSettlementLiquidation={
                                    canRecordSettlementLiquidation
                                }
                                canCloseSettlement={canCloseSettlement}
                                canDownloadSettlementLiquidationReceipt={
                                    canDownloadSettlementLiquidationReceipt
                                }
                                activeApproval={activeApproval}
                            />
                        )}

                        <CardSection
                            icon={
                                <Info className="size-4 text-[var(--brand-blue-600)]" />
                            }
                            title="Datos de la solicitud"
                        >
                            <div className="px-5 py-2">
                                <DetailRow label="Solicitante">
                                    {expenseRequest.user.name}
                                </DetailRow>
                                <DetailRow label="Concepto">
                                    {expenseRequest.concept_label}
                                </DetailRow>
                                {expenseRequest.concept_description ? (
                                    <DetailRow label="Justificación">
                                        <span className="whitespace-pre-wrap">
                                            {expenseRequest.concept_description}
                                        </span>
                                    </DetailRow>
                                ) : null}
                                <DetailRow label="Forma de entrega">
                                    {deliveryLabel}
                                </DetailRow>
                                <DetailRow label="Fecha de creación">
                                    {formatLongDate(expenseRequest.created_at)}
                                </DetailRow>
                                <DetailRow label="Monto solicitado">
                                    <span className="t-num font-semibold">
                                        {formatCentsMx(
                                            expenseRequest.requested_amount_cents,
                                        )}
                                    </span>
                                </DetailRow>
                                {expenseRequest.approved_amount_cents !==
                                null ? (
                                    <DetailRow label="Monto aprobado">
                                        <span className="t-num font-semibold text-[var(--success-fg)]">
                                            {formatCentsMx(
                                                expenseRequest.approved_amount_cents,
                                            )}
                                        </span>
                                    </DetailRow>
                                ) : null}
                            </div>
                        </CardSection>

                        <CardSection
                            icon={
                                <Paperclip className="size-4 text-[var(--brand-blue-600)]" />
                            }
                            title="Evidencia y documentos"
                        >
                            <div className="p-5">
                                <DocumentsSection
                                    expenseRequestId={expenseRequest.id}
                                    attachments={
                                        expenseRequest.submission_attachments
                                    }
                                    canAddAttachments={
                                        canAddSubmissionAttachments
                                    }
                                    downloads={downloadButtons}
                                />
                            </div>
                        </CardSection>

                        {expenseRequest.document_timeline.length > 0 && (
                            <CardSection
                                icon={
                                    <MessageSquare className="size-4 text-[var(--brand-blue-600)]" />
                                }
                                title="Bitácora del documento"
                            >
                                <div className="p-5">
                                    <DocumentTimelineCard
                                        timeline={
                                            expenseRequest.document_timeline
                                        }
                                    />
                                </div>
                            </CardSection>
                        )}
                    </div>

                    {/* ── Right column ─────────────────────── */}
                    <div className="flex flex-col gap-5">
                        <CardSection
                            icon={
                                <GitBranch className="size-4 text-[var(--brand-blue-600)]" />
                            }
                            title="Cadena de aprobación"
                        >
                            <div className="px-5 py-5">
                                <ApprovalTimeline steps={approvalSteps} />
                                {canRebuildWorkflow && (
                                    <RebuildWorkflowAction
                                        expenseRequestId={expenseRequest.id}
                                    />
                                )}
                            </div>
                        </CardSection>

                        <BalanceCard balance={expenseRequest.balance} />

                        {expenseRequest.payment ? (
                            <PaymentSummaryCard
                                expenseRequestId={expenseRequest.id}
                                payment={expenseRequest.payment}
                                canDownloadEvidence={canDownloadPaymentEvidence}
                            />
                        ) : null}
                    </div>
                </div>

                {expenseRequest.expense_reports.length > 0 && (
                    <ExpenseReportsSection
                        reports={expenseRequest.expense_reports}
                        expenseRequestId={expenseRequest.id}
                        canDownloadPdf={
                            canDownloadExpenseReportVerificationPdf
                        }
                        canDownloadXml={
                            canDownloadExpenseReportVerificationXml
                        }
                        canReview={canReviewExpenseReport}
                    />
                )}

                {/* ── Footer nav ─────────────────────────── */}
                <div className="flex justify-center pt-2">
                    <Button variant="outline" size="lg" asChild>
                        <Link href={ExpenseRequestController.index.url()}>
                            <ArrowLeft />
                            Volver al listado
                        </Link>
                    </Button>
                </div>
            </div>
        </AppLayout>
    );
}

function BalanceCard({ balance }: { balance: BalanceSummary }) {
    const remainingNegative = balance.remaining_cents < 0;

    return (
        <section className="overflow-hidden rounded-xl border border-border bg-card">
            <header className="flex items-center gap-2.5 border-b border-border px-5 py-3.5">
                <h2 className="text-sm font-semibold">Balance comprobado</h2>
            </header>
            <div className="px-5 py-3">
                <div className="flex items-baseline justify-between gap-4 py-1">
                    <span className="text-sm text-muted-foreground">
                        Aprobado
                    </span>
                    <span className="t-num text-sm font-medium">
                        {formatCentsMx(balance.approved_cents)}
                    </span>
                </div>
                <div className="flex items-baseline justify-between gap-4 py-1">
                    <span className="text-sm text-muted-foreground">
                        Comprobado
                    </span>
                    <span className="t-num text-sm font-medium">
                        {formatCentsMx(balance.reported_cents)}
                    </span>
                </div>
                <div className="flex items-baseline justify-between gap-4 py-1">
                    <span className="text-sm text-muted-foreground">
                        Restante
                    </span>
                    <span
                        className={`t-num text-sm font-semibold ${remainingNegative ? 'text-red-600 dark:text-red-400' : ''}`}
                    >
                        {formatCentsMx(balance.remaining_cents)}
                    </span>
                </div>
                {balance.over_cap ? (
                    <Alert className="mt-3 border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-200">
                        <AlertTitle>
                            Excediste el monto aprobado
                        </AlertTitle>
                        <AlertDescription>
                            Se requiere una aprobación adicional por el exceso.
                        </AlertDescription>
                    </Alert>
                ) : null}
                {balance.over_cap_pending_extra_approval ? (
                    <Alert className="mt-3 border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-200">
                        <AlertTitle>Aprobación extra requerida</AlertTitle>
                        <AlertDescription>
                            Hay pasos de aprobación pendientes por el exceso de
                            presupuesto.
                        </AlertDescription>
                    </Alert>
                ) : null}
            </div>
        </section>
    );
}

function CardSection({
    icon,
    title,
    children,
}: {
    icon: React.ReactNode;
    title: string;
    children: React.ReactNode;
}) {
    return (
        <section className="overflow-hidden rounded-xl border border-border bg-card">
            <header className="flex items-center gap-2.5 border-b border-border px-5 py-3.5">
                {icon}
                <h2 className="text-sm font-semibold">{title}</h2>
            </header>
            {children}
        </section>
    );
}

function HeaderActions({
    expenseRequestId,
    canUpdate,
    canCancel,
}: {
    expenseRequestId: number;
    canUpdate: boolean;
    canCancel: boolean;
}) {
    const [cancelConfirm, setCancelConfirm] = useState(false);

    if (!canUpdate && !canCancel) {
        return null;
    }

    return (
        <>
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button variant="outline" size="icon" className="size-9">
                        <EllipsisVertical className="size-4" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    {canUpdate && (
                        <DropdownMenuItem asChild>
                            <Link
                                href={ExpenseRequestController.edit.url(
                                    expenseRequestId,
                                )}
                            >
                                <Pencil className="mr-2 size-3.5" />
                                Editar solicitud
                            </Link>
                        </DropdownMenuItem>
                    )}
                    {canCancel && (
                        <DropdownMenuItem
                            variant="destructive"
                            onSelect={() => setCancelConfirm(true)}
                        >
                            <XCircle className="mr-2 size-3.5" />
                            Cancelar solicitud
                        </DropdownMenuItem>
                    )}
                </DropdownMenuContent>
            </DropdownMenu>

            {canCancel && (
                <CancelDialog
                    open={cancelConfirm}
                    onOpenChange={setCancelConfirm}
                    expenseRequestId={expenseRequestId}
                />
            )}
        </>
    );
}

function RebuildWorkflowAction({
    expenseRequestId,
}: {
    expenseRequestId: number;
}) {
    const [open, setOpen] = useState(false);
    const form = useForm({});

    return (
        <>
            <div className="mt-4 border-t border-border pt-4">
                <Button
                    variant="outline"
                    size="sm"
                    className="w-full"
                    onClick={() => setOpen(true)}
                >
                    Rearmar cadena con política vigente
                </Button>
                <p className="mt-2 text-xs text-muted-foreground">
                    Úsalo solo si la cadena quedó desactualizada por un cambio
                    en la política de aprobación.
                </p>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogTitle>
                        ¿Rearmar la cadena de aprobación?
                    </DialogTitle>
                    <DialogDescription>
                        Se eliminarán los pasos pendientes y aprobados de la
                        cadena inicial, y se generará una nueva cadena usando
                        la política activa actual. Esta acción se registra en
                        el historial de la solicitud.
                    </DialogDescription>
                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button variant="secondary">No, volver</Button>
                        </DialogClose>
                        <Button
                            type="button"
                            variant="destructive"
                            disabled={form.processing}
                            onClick={() => {
                                form.post(
                                    `/expense-requests/${expenseRequestId}/approvals/rebuild-workflow`,
                                    {
                                        preserveScroll: true,
                                        onSuccess: () => setOpen(false),
                                    },
                                );
                            }}
                        >
                            {form.processing
                                ? 'Procesando…'
                                : 'Sí, rearmar cadena'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

function CancelDialog({
    open,
    onOpenChange,
    expenseRequestId,
}: {
    open: boolean;
    onOpenChange: (v: boolean) => void;
    expenseRequestId: number;
}) {
    const form = useForm({ note: '' });

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogTitle>¿Cancelar esta solicitud?</DialogTitle>
                <DialogDescription>
                    Escribe un motivo. Esta acción no se puede deshacer.
                </DialogDescription>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post(
                            ExpenseRequestController.cancel.url({
                                expenseRequest: expenseRequestId,
                            }),
                            {
                                preserveScroll: true,
                                onSuccess: () => onOpenChange(false),
                            },
                        );
                    }}
                    className="space-y-3"
                >
                    <Textarea
                        rows={3}
                        required
                        placeholder="Motivo de cancelación..."
                        value={form.data.note}
                        onChange={(e) => form.setData('note', e.target.value)}
                    />
                    <InputError message={form.errors.note} />
                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button variant="secondary">No, volver</Button>
                        </DialogClose>
                        <Button
                            type="submit"
                            variant="destructive"
                            disabled={form.processing}
                        >
                            {form.processing ? 'Procesando…' : 'Sí, cancelar'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
