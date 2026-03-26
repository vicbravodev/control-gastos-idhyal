import { Form } from '@inertiajs/react';
import { XCircle } from 'lucide-react';
import { useState } from 'react';
import ExpenseRequestController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestController';
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

export default function CancelCard({
    expenseRequestId,
}: {
    expenseRequestId: number;
}) {
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [pendingSubmit, setPendingSubmit] = useState<(() => void) | null>(
        null,
    );

    return (
        <Card className="border-destructive/30">
            <CardHeader>
                <CardTitle className="text-destructive">
                    Cancelar solicitud
                </CardTitle>
                <CardDescription>
                    Disponible mientras la solicitud está enviada o en
                    aprobación.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Form
                    {...ExpenseRequestController.cancel.form.post({
                        expenseRequest: expenseRequestId,
                    })}
                    className="space-y-3"
                >
                    {({ processing, errors, submit }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="cancel-note">
                                    Motivo (obligatorio)
                                </Label>
                                <Textarea
                                    id="cancel-note"
                                    name="note"
                                    required
                                    rows={3}
                                    placeholder="Explica por qué cancelar esta solicitud..."
                                />
                                <InputError message={errors.note} />
                            </div>
                            <Button
                                type="button"
                                variant="destructive"
                                size="sm"
                                disabled={processing}
                                onClick={() => {
                                    setPendingSubmit(() => submit);
                                    setConfirmOpen(true);
                                }}
                            >
                                <XCircle className="mr-1.5 size-3.5" />
                                {processing
                                    ? 'Procesando…'
                                    : 'Cancelar solicitud'}
                            </Button>
                            <ConfirmationDialog
                                open={confirmOpen}
                                onOpenChange={setConfirmOpen}
                                title="¿Cancelar esta solicitud?"
                                description="Esta acción cancelará la solicitud de gasto. No se puede deshacer."
                                confirmLabel="Sí, cancelar"
                                variant="destructive"
                                processing={processing}
                                onConfirm={() => {
                                    setConfirmOpen(false);
                                    pendingSubmit?.();
                                }}
                            />
                        </>
                    )}
                </Form>
            </CardContent>
        </Card>
    );
}
