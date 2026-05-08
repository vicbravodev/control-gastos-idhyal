import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft, Paperclip, Save, UploadCloud } from 'lucide-react';

import ExpenseRequestController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestController';
import { PageHeader } from '@/components/idhyal';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
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

type ConceptOption = {
    id: number;
    name: string;
    is_active?: boolean;
};

type FormPayload = {
    id: number;
    requested_amount_cents: number;
    expense_concept_id: number;
    concept_description: string | null;
    delivery_method: string;
    submission_attachments: { id: number; original_filename: string }[];
};

const breadcrumbs = (id: number): BreadcrumbItem[] => [
    { title: 'Dashboard', href: dashboard() },
    {
        title: 'Solicitudes de gasto',
        href: ExpenseRequestController.index.url(),
    },
    {
        title: 'Editar',
        href: ExpenseRequestController.edit.url(id),
    },
];

export default function ExpenseRequestsEdit({
    expenseRequest,
    expenseConcepts = [],
}: {
    expenseRequest: FormPayload;
    expenseConcepts?: ConceptOption[];
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs(expenseRequest.id)}>
            <Head title="Editar solicitud de gasto" />
            <div className="mx-auto flex w-full max-w-3xl animate-fade-in flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    eyebrow="Solicitudes de gasto"
                    title="Editar solicitud"
                    subtitle="Solo puedes editar mientras el estado es enviada. Puedes añadir archivos opcionales."
                    actions={
                        <Button variant="outline" asChild>
                            <Link
                                href={ExpenseRequestController.show.url(
                                    expenseRequest.id,
                                )}
                            >
                                <ArrowLeft />
                                Cancelar
                            </Link>
                        </Button>
                    }
                />
                <Form
                    {...ExpenseRequestController.update.form.patch(
                        expenseRequest.id,
                    )}
                    className="flex flex-col gap-5"
                    options={{
                        preserveScroll: true,
                        forceFormData: true,
                    }}
                >
                    {({ processing, errors }) => (
                        <>
                            <section className="rounded-xl border border-border bg-card p-5 sm:p-6">
                                <header className="mb-4">
                                    <h2 className="text-sm font-semibold">
                                        Datos de la solicitud
                                    </h2>
                                </header>
                                <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="requested_amount_cents">
                                            Monto solicitado
                                        </Label>
                                        <CurrencyInput
                                            id="requested_amount_cents"
                                            name="requested_amount_cents"
                                            defaultValue={
                                                expenseRequest.requested_amount_cents
                                            }
                                            required
                                        />
                                        <InputError
                                            message={
                                                errors.requested_amount_cents
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="expense_concept_id">
                                            Concepto
                                        </Label>
                                        <Select
                                            name="expense_concept_id"
                                            required
                                            defaultValue={String(
                                                expenseRequest.expense_concept_id,
                                            )}
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
                                                        {c.is_active === false
                                                            ? ' (inactivo)'
                                                            : ''}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={errors.expense_concept_id}
                                        />
                                    </div>
                                    <div className="grid gap-2 sm:col-span-2">
                                        <Label htmlFor="concept_description">
                                            Detalle o justificación (opcional)
                                        </Label>
                                        <Textarea
                                            id="concept_description"
                                            name="concept_description"
                                            rows={3}
                                            defaultValue={
                                                expenseRequest.concept_description ??
                                                ''
                                            }
                                        />
                                        <InputError
                                            message={errors.concept_description}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="delivery_method">
                                            Método de entrega
                                        </Label>
                                        <Select
                                            name="delivery_method"
                                            defaultValue={
                                                expenseRequest.delivery_method
                                            }
                                        >
                                            <SelectTrigger id="delivery_method">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="cash">
                                                    Efectivo
                                                </SelectItem>
                                                <SelectItem value="transfer">
                                                    Transferencia
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={errors.delivery_method}
                                        />
                                    </div>
                                </div>
                            </section>

                            <section className="rounded-xl border border-border bg-card p-5 sm:p-6">
                                <header className="mb-4 flex items-center gap-2">
                                    <UploadCloud className="size-4 text-[var(--brand-blue-600)]" />
                                    <h2 className="text-sm font-semibold">
                                        Evidencia
                                    </h2>
                                </header>
                                <div className="grid gap-3">
                                    <Label
                                        htmlFor="attachments"
                                        className="sr-only"
                                    >
                                        Añadir archivos
                                    </Label>
                                    <Input
                                        id="attachments"
                                        name="attachments[]"
                                        type="file"
                                        multiple
                                        accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp"
                                        className="cursor-pointer"
                                    />
                                    <InputError message={errors.attachments} />
                                </div>
                                {expenseRequest.submission_attachments.length >
                                0 ? (
                                    <div className="mt-4 grid gap-2">
                                        <p className="text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                            Archivos ya adjuntos
                                        </p>
                                        <ul className="space-y-1">
                                            {expenseRequest.submission_attachments.map(
                                                (a) => (
                                                    <li
                                                        key={a.id}
                                                        className="flex items-center gap-2 text-sm text-muted-foreground"
                                                    >
                                                        <Paperclip className="size-3.5 shrink-0" />
                                                        {a.original_filename}
                                                    </li>
                                                ),
                                            )}
                                        </ul>
                                    </div>
                                ) : null}
                            </section>

                            <div className="flex flex-wrap gap-2">
                                <Button type="submit" disabled={processing}>
                                    <Save />
                                    {processing
                                        ? 'Guardando…'
                                        : 'Guardar cambios'}
                                </Button>
                                <Button variant="outline" type="button" asChild>
                                    <Link
                                        href={ExpenseRequestController.show.url(
                                            expenseRequest.id,
                                        )}
                                    >
                                        Cancelar
                                    </Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
