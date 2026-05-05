import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import ExpenseRequestController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type ConceptOption = { id: number; name: string };
type DocumentType = 'factura' | 'recibo';

type FormState = {
    reported_amount_cents: number;
    expense_concept_id: string;
    concept_description: string;
    document_type: DocumentType;
    pdf: File | null;
    xml: File | null;
    expense_report?: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    {
        title: 'Solicitudes de gasto',
        href: ExpenseRequestController.index.url(),
    },
    {
        title: 'Comprobación directa',
        href: ExpenseRequestController.createReimbursement.url(),
    },
];

export default function ReimbursementCreate({
    expenseConcepts = [],
}: {
    expenseConcepts?: ConceptOption[];
}) {
    const defaultConceptId =
        expenseConcepts[0]?.id !== undefined
            ? String(expenseConcepts[0].id)
            : '';

    const { data, setData, post, processing, errors } = useForm<FormState>({
        reported_amount_cents: 0,
        expense_concept_id: defaultConceptId,
        concept_description: '',
        document_type: 'factura',
        pdf: null,
        xml: null,
    });

    const isFactura = data.document_type === 'factura';

    function submit(e: FormEvent) {
        e.preventDefault();
        post(ExpenseRequestController.storeReimbursement.url(), {
            forceFormData: true,
            preserveScroll: true,
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Comprobación directa" />
            <div className="mx-auto flex w-full max-w-3xl animate-fade-in flex-col gap-4 p-4">
                <Heading
                    title="Comprobación directa (reembolso)"
                    description="Para gastos que ya pagaste de tu bolsa: sube tu comprobante y la empresa te reembolsará después de la revisión contable."
                />
                <Card>
                    <CardHeader>
                        <CardTitle>Datos de la comprobación</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {expenseConcepts.length === 0 ? (
                            <p className="text-sm text-destructive">
                                No hay conceptos activos en el catálogo. Un
                                administrador debe dar de alta conceptos antes
                                de crear comprobaciones directas.
                            </p>
                        ) : (
                            <form
                                onSubmit={submit}
                                className="flex flex-col gap-5"
                            >
                                <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="reported_amount_cents">
                                            Monto pagado
                                        </Label>
                                        <CurrencyInput
                                            id="reported_amount_cents"
                                            value={data.reported_amount_cents}
                                            onChange={(v) =>
                                                setData(
                                                    'reported_amount_cents',
                                                    v,
                                                )
                                            }
                                            required
                                        />
                                        <InputError
                                            message={
                                                errors.reported_amount_cents
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="expense_concept_id">
                                            Concepto
                                        </Label>
                                        <Select
                                            value={data.expense_concept_id}
                                            onValueChange={(v) =>
                                                setData('expense_concept_id', v)
                                            }
                                        >
                                            <SelectTrigger id="expense_concept_id">
                                                <SelectValue placeholder="Selecciona un concepto" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {expenseConcepts.map((c) => (
                                                    <SelectItem
                                                        key={c.id}
                                                        value={String(c.id)}
                                                    >
                                                        {c.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={errors.expense_concept_id}
                                        />
                                    </div>
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="concept_description">
                                        Descripción adicional (opcional)
                                    </Label>
                                    <Textarea
                                        id="concept_description"
                                        value={data.concept_description}
                                        onChange={(e) =>
                                            setData(
                                                'concept_description',
                                                e.target.value,
                                            )
                                        }
                                        maxLength={2000}
                                    />
                                    <InputError
                                        message={errors.concept_description}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="document_type">
                                        Tipo de documento
                                    </Label>
                                    <Select
                                        value={data.document_type}
                                        onValueChange={(v) => {
                                            setData(
                                                'document_type',
                                                v as DocumentType,
                                            );
                                            if (v === 'recibo') {
                                                setData('xml', null);
                                            }
                                        }}
                                    >
                                        <SelectTrigger id="document_type">
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
                                        message={errors.document_type}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="pdf">PDF (obligatorio)</Label>
                                    <Input
                                        id="pdf"
                                        type="file"
                                        accept=".pdf,application/pdf"
                                        className="cursor-pointer"
                                        onChange={(ev) =>
                                            setData(
                                                'pdf',
                                                ev.target.files?.[0] ?? null,
                                            )
                                        }
                                        required
                                    />
                                    <InputError message={errors.pdf} />
                                </div>
                                {isFactura && (
                                    <div className="grid gap-2">
                                        <Label htmlFor="xml">
                                            XML CFDI (obligatorio para factura)
                                        </Label>
                                        <Input
                                            id="xml"
                                            type="file"
                                            accept=".xml,text/xml,application/xml"
                                            className="cursor-pointer"
                                            onChange={(ev) =>
                                                setData(
                                                    'xml',
                                                    ev.target.files?.[0] ??
                                                        null,
                                                )
                                            }
                                            required={isFactura}
                                        />
                                        <InputError message={errors.xml} />
                                    </div>
                                )}
                                <InputError
                                    message={errors.expense_report}
                                />
                                <p className="text-sm text-muted-foreground">
                                    Esta comprobación entra directamente a
                                    revisión contable. Al aprobarse, la empresa
                                    queda obligada a reembolsarte el monto
                                    comprobado.
                                </p>
                                <div className="flex gap-3 pt-2">
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                    >
                                        {processing
                                            ? 'Enviando…'
                                            : 'Enviar a contabilidad'}
                                    </Button>
                                    <Button variant="outline" asChild>
                                        <Link
                                            href={ExpenseRequestController.index.url()}
                                        >
                                            Cancelar
                                        </Link>
                                    </Button>
                                </div>
                            </form>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
