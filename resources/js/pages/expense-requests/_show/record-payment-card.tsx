import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import ExpenseRequestPaymentController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestPaymentController';
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
import { CurrencyInput } from '@/components/ui/currency-input';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
type PaymentFormData = {
    amount_cents: number;
    payment_method: string;
    paid_on: string;
    transfer_reference: string;
    evidence: File | null;
    payment?: string;
};

export default function RecordPaymentCard({
    expenseRequestId,
    defaultAmountCents,
}: {
    expenseRequestId: number;
    defaultAmountCents: number;
}) {
    const defaultPaidOn = new Date().toISOString().slice(0, 10);
    const form = useForm<PaymentFormData>({
        amount_cents: defaultAmountCents,
        payment_method: 'transfer',
        paid_on: defaultPaidOn,
        transfer_reference: '',
        evidence: null,
    });

    const [confirmOpen, setConfirmOpen] = useState(false);

    return (
        <Card className="border-primary/30">
            <CardHeader>
                <CardTitle>Registrar pago</CardTitle>
                <CardDescription>
                    El monto debe coincidir con el aprobado. Adjunta
                    comprobante.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form
                    className="flex flex-col gap-4"
                    onSubmit={(e) => {
                        e.preventDefault();
                        setConfirmOpen(true);
                    }}
                >
                    <div className="grid gap-2">
                        <Label htmlFor="amount_cents">Monto</Label>
                        <CurrencyInput
                            id="amount_cents"
                            value={form.data.amount_cents}
                            onChange={(v) => form.setData('amount_cents', v)}
                            required
                        />
                        <InputError message={form.errors.amount_cents} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="payment_method">Método de pago</Label>
                        <Select
                            value={form.data.payment_method}
                            onValueChange={(v) =>
                                form.setData('payment_method', v)
                            }
                        >
                            <SelectTrigger id="payment_method">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="transfer">
                                    Transferencia
                                </SelectItem>
                                <SelectItem value="cash">Efectivo</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.payment_method} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="paid_on">Fecha de pago</Label>
                        <input
                            type="hidden"
                            name="paid_on"
                            value={form.data.paid_on}
                        />
                        <DatePicker
                            id="paid_on"
                            value={form.data.paid_on}
                            onChange={(v) => form.setData('paid_on', v)}
                        />
                        <InputError message={form.errors.paid_on} />
                    </div>
                    {form.data.payment_method === 'transfer' && (
                        <div className="grid gap-2">
                            <Label htmlFor="transfer_reference">
                                Referencia de transferencia
                            </Label>
                            <Input
                                id="transfer_reference"
                                type="text"
                                value={form.data.transfer_reference}
                                onChange={(ev) =>
                                    form.setData(
                                        'transfer_reference',
                                        ev.target.value,
                                    )
                                }
                                required
                            />
                            <InputError
                                message={form.errors.transfer_reference}
                            />
                        </div>
                    )}
                    <div className="grid gap-2">
                        <Label htmlFor="evidence">Evidencia de pago</Label>
                        <Input
                            id="evidence"
                            type="file"
                            accept=".pdf,.jpg,.jpeg,.png,image/*,application/pdf"
                            className="cursor-pointer"
                            onChange={(ev) =>
                                form.setData(
                                    'evidence',
                                    ev.target.files?.[0] ?? null,
                                )
                            }
                            required
                        />
                        <InputError message={form.errors.evidence} />
                    </div>
                    <InputError message={form.errors.payment} />
                    <Button type="submit" disabled={form.processing}>
                        {form.processing ? 'Guardando…' : 'Registrar pago'}
                    </Button>
                </form>
            </CardContent>
            <ConfirmationDialog
                open={confirmOpen}
                onOpenChange={setConfirmOpen}
                title="¿Registrar este pago?"
                description="Se registrará el pago y avanzará el flujo de la solicitud."
                confirmLabel="Registrar pago"
                processing={form.processing}
                onConfirm={() => {
                    setConfirmOpen(false);
                    form.post(
                        ExpenseRequestPaymentController.store.url({
                            expenseRequest: expenseRequestId,
                        }),
                        {
                            forceFormData: true,
                            preserveScroll: true,
                        },
                    );
                }}
            />
        </Card>
    );
}
