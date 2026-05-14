import { useForm } from '@inertiajs/react';
import { CheckCircle2, XCircle } from 'lucide-react';
import { useState } from 'react';
import ExpenseReportController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseReportController';
import ConfirmationDialog from '@/components/confirmation-dialog';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatCentsMx } from '@/lib/money';

export default function ExpenseReportReviewCard({
    expenseRequestId,
    expenseReportId,
    reportLabel,
    reportedAmountCents,
}: {
    expenseRequestId: number;
    expenseReportId: number;
    reportLabel?: string | null;
    reportedAmountCents?: number;
}) {
    const approveForm = useForm<{ note: string }>({ note: '' });
    const rejectForm = useForm<{ note: string }>({ note: '' });

    const [confirmReject, setConfirmReject] = useState(false);

    const subjectLabel = reportLabel
        ? `"${reportLabel}"`
        : `#${expenseReportId}`;

    return (
        <Card className="border-amber-300 bg-amber-50/40 dark:border-amber-700 dark:bg-amber-950/30">
            <CardHeader>
                <CardTitle>Revisar comprobación {subjectLabel}</CardTitle>
                <CardDescription>
                    {reportedAmountCents != null ? (
                        <>
                            Estás decidiendo sobre la comprobación{' '}
                            <span className="font-semibold">{subjectLabel}</span>{' '}
                            por un monto de{' '}
                            <span className="font-semibold tabular-nums">
                                {formatCentsMx(reportedAmountCents)}
                            </span>
                            . Aprueba o rechaza con nota obligatoria.
                        </>
                    ) : (
                        <>Aprueba o rechaza esta comprobación con nota obligatoria.</>
                    )}
                </CardDescription>
            </CardHeader>
            <CardContent className="flex flex-col gap-4">
                <form
                    className="flex flex-col gap-3"
                    onSubmit={(e) => {
                        e.preventDefault();
                        approveForm.post(
                            ExpenseReportController.approve[
                                '/expense-requests/{expenseRequest}/expense-reports/{expenseReport}/approve'
                            ].url({
                                expenseRequest: expenseRequestId,
                                expenseReport: expenseReportId,
                            }),
                            { preserveScroll: true },
                        );
                    }}
                >
                    <div className="grid gap-2">
                        <Label htmlFor={`approve-report-note-${expenseReportId}`}>
                            Nota (opcional)
                        </Label>
                        <Textarea
                            id={`approve-report-note-${expenseReportId}`}
                            rows={2}
                            value={approveForm.data.note}
                            onChange={(ev) =>
                                approveForm.setData('note', ev.target.value)
                            }
                        />
                        <InputError message={approveForm.errors.note} />
                    </div>
                    <Button type="submit" disabled={approveForm.processing}>
                        <CheckCircle2 className="mr-1.5 size-3.5" />
                        {approveForm.processing
                            ? 'Procesando…'
                            : `Aprobar comprobación ${subjectLabel}`}
                    </Button>
                </form>
                <form
                    className="flex flex-col gap-3 border-t pt-4"
                    onSubmit={(e) => {
                        e.preventDefault();
                        setConfirmReject(true);
                    }}
                >
                    <div className="grid gap-2">
                        <Label htmlFor={`reject-report-note-${expenseReportId}`}>
                            Rechazar (nota obligatoria)
                        </Label>
                        <Textarea
                            id={`reject-report-note-${expenseReportId}`}
                            rows={3}
                            value={rejectForm.data.note}
                            onChange={(ev) =>
                                rejectForm.setData('note', ev.target.value)
                            }
                            required
                        />
                        <InputError message={rejectForm.errors.note} />
                    </div>
                    <Button
                        type="submit"
                        variant="destructive"
                        disabled={rejectForm.processing}
                    >
                        <XCircle className="mr-1.5 size-3.5" />
                        {rejectForm.processing
                            ? 'Procesando…'
                            : `Rechazar comprobación ${subjectLabel}`}
                    </Button>
                </form>
            </CardContent>
            <ConfirmationDialog
                open={confirmReject}
                onOpenChange={setConfirmReject}
                title={`¿Rechazar la comprobación ${subjectLabel}?`}
                description="Se notificará al solicitante y deberá volver a presentarla."
                confirmLabel="Rechazar"
                variant="destructive"
                processing={rejectForm.processing}
                onConfirm={() => {
                    setConfirmReject(false);
                    rejectForm.post(
                        ExpenseReportController.reject[
                            '/expense-requests/{expenseRequest}/expense-reports/{expenseReport}/reject'
                        ].url({
                            expenseRequest: expenseRequestId,
                            expenseReport: expenseReportId,
                        }),
                        { preserveScroll: true },
                    );
                }}
            />
        </Card>
    );
}
