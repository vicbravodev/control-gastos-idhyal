import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import ExpenseConceptController from '@/actions/App/Http/Controllers/ExpenseConcepts/ExpenseConceptController';
import ConfirmationDialog from '@/components/confirmation-dialog';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type ConceptPayload = {
    id: number;
    name: string;
    is_active: boolean;
    sort_order: number;
    expense_requests_count: number;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    {
        title: 'Conceptos de gasto',
        href: ExpenseConceptController.index.url(),
    },
    { title: 'Editar', href: '#' },
];

export default function ExpenseConceptsEdit({
    concept,
    can,
}: {
    concept: ConceptPayload;
    can: { delete: boolean };
}) {
    const { data, setData, put, processing, errors } = useForm({
        name: concept.name,
        is_active: concept.is_active,
        sort_order: concept.sort_order,
    });

    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [deleting, setDeleting] = useState(false);

    function submit(e: FormEvent) {
        e.preventDefault();
        put(ExpenseConceptController.update.url(concept.id), {
            preserveScroll: true,
        });
    }

    function handleDelete() {
        if (!can.delete) {
            return;
        }

        setShowDeleteDialog(true);
    }

    function handleConfirmDelete() {
        if (!can.delete) {
            return;
        }

        setDeleting(true);
        router.delete(ExpenseConceptController.destroy.url(concept.id), {
            preserveScroll: true,
            onFinish: () => {
                setDeleting(false);
                setShowDeleteDialog(false);
            },
        });
    }

    return (
        <AppLayout
            breadcrumbs={breadcrumbs.map((b, i) =>
                i === breadcrumbs.length - 1
                    ? {
                          ...b,
                          href: ExpenseConceptController.edit.url(concept.id),
                      }
                    : b,
            )}
        >
            <Head title={`Editar: ${concept.name}`} />
            <div className="relative mx-auto flex w-full max-w-2xl animate-fade-in flex-col gap-4 p-4">
                <div
                    className="pointer-events-none absolute inset-0 -z-10 rounded-xl opacity-[0.06] dark:opacity-[0.1]"
                    style={{
                        background:
                            'radial-gradient(ellipse 80% 60% at 100% 0%, currentColor, transparent 55%)',
                    }}
                />
                <Heading
                    title="Editar concepto"
                    description={
                        concept.expense_requests_count > 0
                            ? `${concept.expense_requests_count} solicitud(es) usan este concepto. Puedes desactivarlo para ocultarlo en formularios nuevos.`
                            : 'Sin solicitudes vinculadas; puedes eliminar de forma segura.'
                    }
                />
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base font-semibold">
                            Datos
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="flex flex-col gap-5">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nombre</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) =>
                                        setData('name', e.target.value)
                                    }
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="sort_order">Orden</Label>
                                <Input
                                    id="sort_order"
                                    type="number"
                                    min={0}
                                    max={65535}
                                    value={data.sort_order}
                                    onChange={(e) =>
                                        setData(
                                            'sort_order',
                                            Number(e.target.value) || 0,
                                        )
                                    }
                                />
                                <InputError message={errors.sort_order} />
                            </div>
                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="is_active"
                                    checked={data.is_active}
                                    onCheckedChange={(v) =>
                                        setData('is_active', v === true)
                                    }
                                />
                                <Label
                                    htmlFor="is_active"
                                    className="cursor-pointer font-normal"
                                >
                                    Activo
                                </Label>
                            </div>
                            <InputError message={errors.is_active} />
                            <div className="flex flex-wrap gap-2 pt-2">
                                <Button type="submit" disabled={processing}>
                                    Guardar cambios
                                </Button>
                                <Button variant="outline" type="button" asChild>
                                    <Link
                                        href={ExpenseConceptController.index.url()}
                                    >
                                        Volver
                                    </Link>
                                </Button>
                                {can.delete ? (
                                    <Button
                                        variant="destructive"
                                        type="button"
                                        className="ms-auto"
                                        onClick={handleDelete}
                                    >
                                        Eliminar
                                    </Button>
                                ) : null}
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
            <ConfirmationDialog
                open={showDeleteDialog}
                onOpenChange={(open) => {
                    if (!open) {
                        setShowDeleteDialog(false);
                    }
                }}
                title="Eliminar concepto"
                description="¿Eliminar este concepto? Solo es posible si no hay solicitudes vinculadas."
                confirmLabel="Eliminar"
                variant="destructive"
                processing={deleting}
                onConfirm={handleConfirmDelete}
            />
        </AppLayout>
    );
}
