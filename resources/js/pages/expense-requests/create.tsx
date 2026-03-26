import { Form, Head, Link } from '@inertiajs/react';
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

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    {
        title: 'Solicitudes de gasto',
        href: ExpenseRequestController.index.url(),
    },
    {
        title: 'Nueva',
        href: ExpenseRequestController.create.url(),
    },
];

export default function ExpenseRequestsCreate({
    expenseConcepts = [],
}: {
    expenseConcepts?: ConceptOption[];
}) {
    const defaultConceptId =
        expenseConcepts[0]?.id !== undefined
            ? String(expenseConcepts[0].id)
            : '';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva solicitud de gasto" />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-4 p-4 animate-fade-in">
                <Heading
                    title="Nueva solicitud de gasto"
                    description="Completa el formulario para crear una solicitud de gasto. El monto se captura en pesos mexicanos."
                />
                <Card>
                    <CardHeader>
                        <CardTitle>Datos de la solicitud</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Form
                            {...ExpenseRequestController.store.form()}
                            className="flex flex-col gap-5"
                            options={{
                                preserveScroll: true,
                                forceFormData: true,
                            }}
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="requested_amount_cents">
                                                Monto solicitado
                                            </Label>
                                            <CurrencyInput
                                                id="requested_amount_cents"
                                                name="requested_amount_cents"
                                                required
                                                placeholder="$0.00"
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
                                            {expenseConcepts.length === 0 ? (
                                                <p className="text-sm text-destructive">
                                                    No hay conceptos activos en
                                                    el catálogo. Un
                                                    administrador debe dar de
                                                    alta conceptos antes de
                                                    crear solicitudes.
                                                </p>
                                            ) : (
                                                <Select
                                                    name="expense_concept_id"
                                                    required
                                                    defaultValue={
                                                        defaultConceptId
                                                    }
                                                >
                                                    <SelectTrigger id="expense_concept_id">
                                                        <SelectValue placeholder="Selecciona un concepto" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {expenseConcepts.map(
                                                            (c) => (
                                                                <SelectItem
                                                                    key={c.id}
                                                                    value={String(
                                                                        c.id,
                                                                    )}
                                                                >
                                                                    {c.name}
                                                                </SelectItem>
                                                            ),
                                                        )}
                                                    </SelectContent>
                                                </Select>
                                            )}
                                            <InputError
                                                message={
                                                    errors.expense_concept_id
                                                }
                                            />
                                        </div>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="concept_description">
                                            Detalle o justificación (opcional)
                                        </Label>
                                        <Textarea
                                            id="concept_description"
                                            name="concept_description"
                                            rows={3}
                                            placeholder="Contexto adicional del gasto…"
                                        />
                                        <InputError
                                            message={
                                                errors.concept_description
                                            }
                                        />
                                    </div>
                                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="delivery_method">
                                                Método de entrega
                                            </Label>
                                            <Select
                                                name="delivery_method"
                                                defaultValue="cash"
                                            >
                                                <SelectTrigger id="delivery_method">
                                                    <SelectValue placeholder="Selecciona un método" />
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
                                                message={
                                                    errors.delivery_method
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="attachments">
                                                Archivos opcionales
                                            </Label>
                                            <Input
                                                id="attachments"
                                                name="attachments[]"
                                                type="file"
                                                multiple
                                                accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp"
                                                className="cursor-pointer"
                                            />
                                            <p className="text-xs text-muted-foreground">
                                                PDF o imagen, hasta 10 archivos
                                                (10 MB c/u). No sustituye la
                                                comprobación posterior al pago.
                                            </p>
                                            <InputError
                                                message={errors.attachments}
                                            />
                                        </div>
                                    </div>
                                    <InputError
                                        message={errors.approval_policy}
                                    />
                                    <div className="flex flex-wrap gap-2 border-t pt-4">
                                        <Button
                                            type="submit"
                                            disabled={
                                                processing ||
                                                expenseConcepts.length === 0
                                            }
                                        >
                                            {processing
                                                ? 'Guardando…'
                                                : 'Crear y enviar'}
                                        </Button>
                                        <Button
                                            variant="outline"
                                            type="button"
                                            asChild
                                        >
                                            <Link
                                                href={ExpenseRequestController.index.url()}
                                            >
                                                Cancelar
                                            </Link>
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
