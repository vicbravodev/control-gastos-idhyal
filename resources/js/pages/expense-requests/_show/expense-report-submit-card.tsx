import { useForm } from '@inertiajs/react';
import ExpenseReportController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseReportController';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type DocumentType = 'factura' | 'recibo';

type ReportFormState = {
    reported_amount_cents: number;
    document_type: DocumentType;
    label: string;
    pdf: File | null;
    xml: File | null;
    expense_report?: string;
};

export default function ExpenseReportSubmitCard({
    expenseRequestId,
    defaultReportedCents,
    canSubmit,
}: {
    expenseRequestId: number;
    defaultReportedCents: number;
    canSubmit: boolean;
}) {
    const submitForm = useForm<ReportFormState>({
        reported_amount_cents: defaultReportedCents,
        document_type: 'factura',
        label: '',
        pdf: null,
        xml: null,
    });

    const submitIsFactura = submitForm.data.document_type === 'factura';

    if (!canSubmit) {
        return null;
    }

    return (
        <Card className="border-primary/30">
            <CardHeader>
                <CardTitle>Presentar comprobación</CardTitle>
                <CardDescription>
                    Adjunta el PDF (y XML cuando sea factura) y envía a
                    contabilidad. No se guardan borradores: si la comprobación
                    aún no está lista, vuelve más tarde.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form
                    className="flex flex-col gap-4"
                    onSubmit={(e) => {
                        e.preventDefault();
                        submitForm.post(
                            ExpenseReportController.submit[
                                '/expense-requests/{expenseRequest}/expense-reports'
                            ].url({
                                expenseRequest: expenseRequestId,
                            }),
                            {
                                forceFormData: true,
                                preserveScroll: true,
                            },
                        );
                    }}
                >
                    <div className="grid gap-2">
                        <Label htmlFor="submit-label">
                            Etiqueta (ej. Hotel, Vuelo, Viáticos)
                        </Label>
                        <Input
                            id="submit-label"
                            type="text"
                            value={submitForm.data.label}
                            onChange={(ev) =>
                                submitForm.setData('label', ev.target.value)
                            }
                            placeholder="Opcional"
                            maxLength={80}
                        />
                        <InputError message={submitForm.errors.label} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="submit-document-type">
                            Tipo de documento
                        </Label>
                        <Select
                            value={submitForm.data.document_type}
                            onValueChange={(v) => {
                                submitForm.setData(
                                    'document_type',
                                    v as DocumentType,
                                );

                                if (v === 'recibo') {
                                    submitForm.setData('xml', null);
                                }
                            }}
                        >
                            <SelectTrigger id="submit-document-type">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="factura">
                                    Factura (CFDI)
                                </SelectItem>
                                <SelectItem value="recibo">
                                    Recibo (sin CFDI)
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError
                            message={submitForm.errors.document_type}
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="submit-reported">Monto comprobado</Label>
                        <CurrencyInput
                            id="submit-reported"
                            value={submitForm.data.reported_amount_cents}
                            onChange={(v) =>
                                submitForm.setData(
                                    'reported_amount_cents',
                                    v,
                                )
                            }
                            required
                        />
                        <InputError
                            message={submitForm.errors.reported_amount_cents}
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="submit-pdf">PDF (obligatorio)</Label>
                        <Input
                            id="submit-pdf"
                            type="file"
                            accept=".pdf,application/pdf"
                            className="cursor-pointer"
                            onChange={(ev) =>
                                submitForm.setData(
                                    'pdf',
                                    ev.target.files?.[0] ?? null,
                                )
                            }
                            required
                        />
                        <InputError message={submitForm.errors.pdf} />
                    </div>
                    {submitIsFactura && (
                        <div className="grid gap-2">
                            <Label htmlFor="submit-xml">
                                XML CFDI (obligatorio para factura)
                            </Label>
                            <Input
                                id="submit-xml"
                                type="file"
                                accept=".xml,text/xml,application/xml"
                                className="cursor-pointer"
                                onChange={(ev) =>
                                    submitForm.setData(
                                        'xml',
                                        ev.target.files?.[0] ?? null,
                                    )
                                }
                                required={submitIsFactura}
                            />
                            <InputError message={submitForm.errors.xml} />
                        </div>
                    )}
                    <InputError message={submitForm.errors.expense_report} />
                    <Button type="submit" disabled={submitForm.processing}>
                        {submitForm.processing
                            ? 'Enviando…'
                            : 'Enviar a contabilidad'}
                    </Button>
                </form>
            </CardContent>
        </Card>
    );
}
