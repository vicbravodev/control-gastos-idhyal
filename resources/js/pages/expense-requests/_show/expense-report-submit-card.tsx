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
type ReportFormState = {
    reported_amount_cents: number;
    pdf: File | null;
    xml: File | null;
    expense_report?: string;
};

export default function ExpenseReportSubmitCard({
    expenseRequestId,
    defaultReportedCents,
    canSaveDraft,
    canSubmit,
}: {
    expenseRequestId: number;
    defaultReportedCents: number;
    canSaveDraft: boolean;
    canSubmit: boolean;
}) {
    const draftForm = useForm<ReportFormState>({
        reported_amount_cents: defaultReportedCents,
        pdf: null,
        xml: null,
    });

    const submitForm = useForm<ReportFormState>({
        reported_amount_cents: defaultReportedCents,
        pdf: null,
        xml: null,
    });

    return (
        <Card className="border-primary/30">
            <CardHeader>
                <CardTitle>Presentar comprobación</CardTitle>
                <CardDescription>
                    Guarda borrador o envía a contabilidad (requiere PDF y XML).
                </CardDescription>
            </CardHeader>
            <CardContent className="flex flex-col gap-6">
                {canSaveDraft && (
                    <form
                        className="flex flex-col gap-4 border-b pb-6"
                        onSubmit={(e) => {
                            e.preventDefault();
                            draftForm.post(
                                ExpenseReportController.storeDraft.url({
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
                            <Label htmlFor="draft-reported">
                                Monto comprobado
                            </Label>
                            <CurrencyInput
                                id="draft-reported"
                                value={draftForm.data.reported_amount_cents}
                                onChange={(v) =>
                                    draftForm.setData(
                                        'reported_amount_cents',
                                        v,
                                    )
                                }
                                required
                            />
                            <InputError
                                message={
                                    draftForm.errors.reported_amount_cents
                                }
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="draft-pdf">
                                PDF (opcional en borrador)
                            </Label>
                            <Input
                                id="draft-pdf"
                                type="file"
                                accept=".pdf,application/pdf"
                                className="cursor-pointer"
                                onChange={(ev) =>
                                    draftForm.setData(
                                        'pdf',
                                        ev.target.files?.[0] ?? null,
                                    )
                                }
                            />
                            <InputError message={draftForm.errors.pdf} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="draft-xml">
                                XML (opcional en borrador)
                            </Label>
                            <Input
                                id="draft-xml"
                                type="file"
                                accept=".xml,text/xml,application/xml"
                                className="cursor-pointer"
                                onChange={(ev) =>
                                    draftForm.setData(
                                        'xml',
                                        ev.target.files?.[0] ?? null,
                                    )
                                }
                            />
                            <InputError message={draftForm.errors.xml} />
                        </div>
                        <InputError
                            message={draftForm.errors.expense_report}
                        />
                        <Button
                            type="submit"
                            variant="secondary"
                            disabled={draftForm.processing}
                        >
                            {draftForm.processing
                                ? 'Guardando…'
                                : 'Guardar borrador'}
                        </Button>
                    </form>
                )}
                {canSubmit && (
                    <form
                        className="flex flex-col gap-4"
                        onSubmit={(e) => {
                            e.preventDefault();
                            submitForm.post(
                                ExpenseReportController.submit.url({
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
                            <Label htmlFor="submit-reported">
                                Monto comprobado
                            </Label>
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
                                message={
                                    submitForm.errors.reported_amount_cents
                                }
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="submit-pdf">
                                PDF (obligatorio)
                            </Label>
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
                        <div className="grid gap-2">
                            <Label htmlFor="submit-xml">
                                XML (obligatorio)
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
                                required
                            />
                            <InputError message={submitForm.errors.xml} />
                        </div>
                        <InputError
                            message={submitForm.errors.expense_report}
                        />
                        <Button
                            type="submit"
                            disabled={submitForm.processing}
                        >
                            {submitForm.processing
                                ? 'Enviando…'
                                : 'Enviar a contabilidad'}
                        </Button>
                    </form>
                )}
            </CardContent>
        </Card>
    );
}
