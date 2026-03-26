import { Form } from '@inertiajs/react';
import { CheckCircle2, XCircle } from 'lucide-react';
import { useState } from 'react';
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
import type { ApprovalRow } from '@/types';

type FormAction = Record<string, unknown>;

export default function ActiveApprovalCard({
    approval,
    approveAction,
    rejectAction,
}: {
    approval: ApprovalRow;
    approveAction: FormAction;
    rejectAction: FormAction;
}) {
    const [confirmApprove, setConfirmApprove] = useState(false);
    const [approveSubmit, setApproveSubmit] = useState<(() => void) | null>(
        null,
    );

    return (
        <Card className="border-primary/30">
            <CardHeader>
                <CardTitle>Tu aprobación</CardTitle>
                <CardDescription>
                    Paso {approval.step_order} — {approval.role.name}
                </CardDescription>
            </CardHeader>
            <CardContent className="flex flex-col gap-4">
                <Form {...approveAction}>
                    {({ processing, errors, submit }) => (
                        <>
                            <InputError message={errors.approval} />
                            <Button
                                type="button"
                                disabled={processing}
                                onClick={() => {
                                    setApproveSubmit(() => submit);
                                    setConfirmApprove(true);
                                }}
                            >
                                <CheckCircle2 className="mr-1.5 size-3.5" />
                                {processing ? 'Procesando…' : 'Aprobar'}
                            </Button>
                            <ConfirmationDialog
                                open={confirmApprove}
                                onOpenChange={setConfirmApprove}
                                title="¿Aprobar esta solicitud?"
                                description="Esta acción registrará tu aprobación y avanzará el flujo."
                                confirmLabel="Aprobar"
                                processing={processing}
                                onConfirm={() => {
                                    setConfirmApprove(false);
                                    approveSubmit?.();
                                }}
                            />
                        </>
                    )}
                </Form>
                <Form {...rejectAction} className="space-y-3 border-t pt-4">
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="reject-note">
                                    Rechazar (nota obligatoria)
                                </Label>
                                <Textarea
                                    id="reject-note"
                                    name="note"
                                    required
                                    rows={3}
                                    placeholder="Motivo del rechazo..."
                                />
                                <InputError message={errors.note} />
                            </div>
                            <Button
                                type="submit"
                                variant="destructive"
                                disabled={processing}
                            >
                                <XCircle className="mr-1.5 size-3.5" />
                                {processing ? 'Procesando…' : 'Rechazar'}
                            </Button>
                        </>
                    )}
                </Form>
            </CardContent>
        </Card>
    );
}
