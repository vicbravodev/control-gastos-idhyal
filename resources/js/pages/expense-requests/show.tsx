import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    CheckCircle2,
    EllipsisVertical,
    Pencil,
    XCircle,
} from 'lucide-react';
import { useState } from 'react';
import ExpenseRequestController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { StatusBadge } from '@/components/status-badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { formatCentsMx } from '@/lib/money';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import ActionRequiredCard from './_show/action-required-card';
import DocumentTimelineCard from './_show/document-timeline-card';
import DocumentsSection from './_show/documents-section';
import ProgressStepper from './_show/progress-stepper';
import type { Detail } from './_show/types';

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
    canSaveExpenseReportDraft,
    canSubmitExpenseReport,
    canReviewExpenseReport,
    canRecordSettlementLiquidation,
    canCloseSettlement,
    canCancel,
    canAddSubmissionAttachments,
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
    canSaveExpenseReportDraft: boolean;
    canSubmitExpenseReport: boolean;
    canReviewExpenseReport: boolean;
    canRecordSettlementLiquidation: boolean;
    canCloseSettlement: boolean;
    canCancel: boolean;
    canAddSubmissionAttachments: boolean;
    activeApprovalId: number | null;
}) {
    const { flash } = usePage<{ flash?: { status?: string } }>().props;

    const activeApproval = expenseRequest.approvals.find(
        (a) => a.id === activeApprovalId,
    );

    const hasAction =
        !!activeApproval ||
        canRecordPayment ||
        canSaveExpenseReportDraft ||
        canSubmitExpenseReport ||
        canReviewExpenseReport ||
        canRecordSettlementLiquidation ||
        canCloseSettlement;

    const deliveryLabel =
        expenseRequest.delivery_method === 'cash'
            ? 'Efectivo'
            : expenseRequest.delivery_method === 'transfer'
              ? 'Transferencia'
              : expenseRequest.delivery_method;

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
                canDownloadExpenseReportVerificationPdf &&
                expenseRequest.expense_report
                    ?.verification_pdf_attachment_id != null,
            href:
                expenseRequest.expense_report
                    ?.verification_pdf_attachment_id != null
                    ? ExpenseRequestController.downloadExpenseReportVerificationAttachment.url(
                          {
                              expense_request: expenseRequest.id,
                              attachment:
                                  expenseRequest.expense_report
                                      .verification_pdf_attachment_id,
                          },
                      )
                    : '#',
            label: 'PDF comprobación',
        },
        {
            show:
                canDownloadExpenseReportVerificationXml &&
                expenseRequest.expense_report
                    ?.verification_xml_attachment_id != null,
            href:
                expenseRequest.expense_report
                    ?.verification_xml_attachment_id != null
                    ? ExpenseRequestController.downloadExpenseReportVerificationAttachment.url(
                          {
                              expense_request: expenseRequest.id,
                              attachment:
                                  expenseRequest.expense_report
                                      .verification_xml_attachment_id,
                          },
                      )
                    : '#',
            label: 'XML comprobación',
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

    const isTerminal = ['rejected', 'cancelled', 'closed'].includes(
        expenseRequest.status,
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs(expenseRequest.id)}>
            <Head
                title={
                    expenseRequest.folio
                        ? `Solicitud ${expenseRequest.folio}`
                        : 'Solicitud de gasto'
                }
            />
            <div className="mx-auto flex w-full max-w-4xl flex-col gap-8 p-4 pb-16 animate-fade-in">
                {flash?.status && (
                    <Alert className="border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950">
                        <CheckCircle2 className="size-4 text-emerald-600" />
                        <AlertTitle>Listo</AlertTitle>
                        <AlertDescription>{flash.status}</AlertDescription>
                    </Alert>
                )}

                {/* ── Header ─────────────────────────────── */}
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="space-y-1">
                        <div className="flex items-center gap-3">
                            <Heading
                                title={
                                    expenseRequest.folio ??
                                    `Solicitud #${expenseRequest.id}`
                                }
                                description={`Solicitante: ${expenseRequest.user.name}`}
                            />
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <StatusBadge
                            status={expenseRequest.status}
                            className="text-sm px-3 py-1"
                        />
                        <HeaderActions
                            expenseRequestId={expenseRequest.id}
                            canUpdate={canUpdate}
                            canCancel={canCancel}
                        />
                    </div>
                </div>

                {/* ── Action required ────────────────────── */}
                {hasAction && (
                    <ActionRequiredCard
                        expenseRequest={expenseRequest}
                        canRecordPayment={canRecordPayment}
                        canSaveExpenseReportDraft={canSaveExpenseReportDraft}
                        canSubmitExpenseReport={canSubmitExpenseReport}
                        canReviewExpenseReport={canReviewExpenseReport}
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

                {/* ── Request summary ────────────────────── */}
                <Card>
                    <CardContent className="pt-6">
                        <p className="mb-4 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                            Resumen de solicitud
                        </p>
                        <dl className="grid grid-cols-2 gap-x-6 gap-y-3 text-sm sm:grid-cols-3">
                            <div>
                                <dt className="text-muted-foreground">
                                    Monto solicitado
                                </dt>
                                <dd className="mt-0.5 text-base font-semibold tabular-nums">
                                    {formatCentsMx(
                                        expenseRequest.requested_amount_cents,
                                    )}
                                </dd>
                            </div>
                            {expenseRequest.approved_amount_cents !== null && (
                                <div>
                                    <dt className="text-muted-foreground">
                                        Monto aprobado
                                    </dt>
                                    <dd className="mt-0.5 text-base font-semibold tabular-nums text-emerald-700 dark:text-emerald-400">
                                        {formatCentsMx(
                                            expenseRequest.approved_amount_cents,
                                        )}
                                    </dd>
                                </div>
                            )}
                            <div>
                                <dt className="text-muted-foreground">
                                    Entrega
                                </dt>
                                <dd className="mt-0.5 font-medium">
                                    {deliveryLabel}
                                </dd>
                            </div>
                        </dl>
                        <Separator className="my-4" />
                        <div>
                            <p className="mb-1 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                Concepto
                            </p>
                            <p className="text-sm font-medium leading-relaxed">
                                {expenseRequest.concept_label}
                            </p>
                            {expenseRequest.concept_description ? (
                                <p className="mt-2 whitespace-pre-wrap text-sm leading-relaxed text-muted-foreground">
                                    {expenseRequest.concept_description}
                                </p>
                            ) : null}
                        </div>
                    </CardContent>
                </Card>

                {/* ── Progress stepper ───────────────────── */}
                <div className="space-y-3">
                    <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                        Progreso
                    </p>
                    <Card>
                        <CardContent className="pt-6">
                            <ProgressStepper
                                requestStatus={expenseRequest.status}
                                approvals={expenseRequest.approvals}
                                approvalProgress={
                                    expenseRequest.approval_progress
                                }
                                payment={expenseRequest.payment}
                                expenseReport={expenseRequest.expense_report}
                                settlement={expenseRequest.settlement}
                            />
                        </CardContent>
                    </Card>
                </div>

                {/* ── Documents & downloads ──────────────── */}
                <Card>
                    <CardContent className="pt-6">
                        <DocumentsSection
                            expenseRequestId={expenseRequest.id}
                            attachments={
                                expenseRequest.submission_attachments
                            }
                            canAddAttachments={canAddSubmissionAttachments}
                            downloads={downloadButtons}
                        />
                    </CardContent>
                </Card>

                {/* ── Timeline ───────────────────────────── */}
                <Card>
                    <CardContent className="pt-6">
                        <DocumentTimelineCard
                            timeline={expenseRequest.document_timeline}
                        />
                    </CardContent>
                </Card>

                {/* ── Footer nav ─────────────────────────── */}
                <div className="flex justify-center">
                    <Button variant="outline" size="lg" asChild>
                        <Link href={ExpenseRequestController.index.url()}>
                            Volver al listado
                        </Link>
                    </Button>
                </div>
            </div>
        </AppLayout>
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

    if (!canUpdate && !canCancel) return null;

    return (
        <>
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button variant="ghost" size="icon" className="size-9">
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
                            {form.processing
                                ? 'Procesando…'
                                : 'Sí, cancelar'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
