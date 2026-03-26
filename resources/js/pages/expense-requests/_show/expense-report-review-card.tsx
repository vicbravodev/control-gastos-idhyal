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

export default function ExpenseReportReviewCard({
    expenseRequestId,
}: {
    expenseRequestId: number;
}) {
    const approveForm = useForm<{ note: string; expense_report?: string }>({
        note: '',
    });
    const rejectForm = useForm<{ note: string; expense_report?: string }>({
        note: '',
    });

    const [confirmReject, setConfirmReject] = useState(false);

    return (
        <Card className="border-primary/30">
            <CardHeader>
                <CardTitle>Revisión de comprobación</CardTitle>
                <CardDescription>
                    Aprueba o rechaza con nota obligatoria.
                </CardDescription>
            </CardHeader>
            <CardContent className="flex flex-col gap-4">
                <form
                    className="flex flex-col gap-3"
                    onSubmit={(e) => {
                        e.preventDefault();
                        approveForm.post(
                            ExpenseReportController.approve.url({
                                expenseRequest: expenseRequestId,
                            }),
                            { preserveScroll: true },
                        );
                    }}
                >
                    <div className="grid gap-2">
                        <Label htmlFor="approve-report-note">
                            Nota (opcional)
                        </Label>
                        <Textarea
                            id="approve-report-note"
                            rows={2}
                            value={approveForm.data.note}
                            onChange={(ev) =>
                                approveForm.setData('note', ev.target.value)
                            }
                        />
                        <InputError message={approveForm.errors.note} />
                    </div>
                    <InputError
                        message={approveForm.errors.expense_report}
                    />
                    <Button
                        type="submit"
                        disabled={approveForm.processing}
                    >
                        <CheckCircle2 className="mr-1.5 size-3.5" />
                        {approveForm.processing
                            ? 'Procesando…'
                            : 'Aprobar comprobación'}
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
                        <Label htmlFor="reject-report-note">
                            Rechazar (nota obligatoria)
                        </Label>
                        <Textarea
                            id="reject-report-note"
                            rows={3}
                            value={rejectForm.data.note}
                            onChange={(ev) =>
                                rejectForm.setData('note', ev.target.value)
                            }
                            required
                        />
                        <InputError message={rejectForm.errors.note} />
                    </div>
                    <InputError
                        message={rejectForm.errors.expense_report}
                    />
                    <Button
                        type="submit"
                        variant="destructive"
                        disabled={rejectForm.processing}
                    >
                        <XCircle className="mr-1.5 size-3.5" />
                        {rejectForm.processing
                            ? 'Procesando…'
                            : 'Rechazar comprobación'}
                    </Button>
                </form>
            </CardContent>
            <ConfirmationDialog
                open={confirmReject}
                onOpenChange={setConfirmReject}
                title="¿Rechazar esta comprobación?"
                description="Se notificará al solicitante y deberá volver a presentarla."
                confirmLabel="Rechazar"
                variant="destructive"
                processing={rejectForm.processing}
                onConfirm={() => {
                    setConfirmReject(false);
                    rejectForm.post(
                        ExpenseReportController.reject.url({
                            expenseRequest: expenseRequestId,
                        }),
                        { preserveScroll: true },
                    );
                }}
            />
        </Card>
    );
}
