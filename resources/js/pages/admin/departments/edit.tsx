import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import DepartmentController from '@/actions/App/Http/Controllers/Admin/DepartmentController';
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

type DepartmentPayload = {
    id: number;
    code: string;
    name: string;
    is_active: boolean;
    position: number;
};

export default function AdminDepartmentsEdit({
    department,
    can,
}: {
    department: DepartmentPayload;
    can: { delete: boolean };
}) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Departamentos', href: DepartmentController.index.url() },
        {
            title: department.name,
            href: DepartmentController.edit.url(department.id),
        },
    ];

    const { data, setData, patch, processing, errors } = useForm({
        code: department.code,
        name: department.name,
        is_active: department.is_active,
        position: department.position,
    });

    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [deleting, setDeleting] = useState(false);

    function submit(e: FormEvent) {
        e.preventDefault();
        patch(DepartmentController.update.url(department.id), {
            preserveScroll: true,
        });
    }

    function handleConfirmDelete() {
        if (!can.delete) {
            return;
        }

        setDeleting(true);
        router.delete(DepartmentController.destroy.url(department.id), {
            onFinish: () => {
                setDeleting(false);
                setShowDeleteDialog(false);
            },
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar departamento — ${department.name}`} />
            <div className="relative mx-auto flex w-full max-w-2xl animate-fade-in flex-col gap-4 p-4">
                <Heading
                    title="Editar departamento"
                    description="Actualice datos. Elimine solo si no quedan usuarios asignados."
                />
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base font-semibold">
                            Datos
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form
                            onSubmit={submit}
                            className="flex flex-col gap-5"
                        >
                            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="code">Código</Label>
                                    <Input
                                        id="code"
                                        value={data.code}
                                        onChange={(e) =>
                                            setData('code', e.target.value)
                                        }
                                        required
                                        maxLength={32}
                                        className="font-mono"
                                    />
                                    <InputError message={errors.code} />
                                </div>
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
                                    <Label htmlFor="position">Orden</Label>
                                    <Input
                                        id="position"
                                        type="number"
                                        min={0}
                                        value={data.position}
                                        onChange={(e) =>
                                            setData(
                                                'position',
                                                Number(e.target.value),
                                            )
                                        }
                                    />
                                    <InputError message={errors.position} />
                                </div>
                                <div className="flex items-center gap-2 pt-6">
                                    <Checkbox
                                        id="is_active"
                                        checked={data.is_active}
                                        onCheckedChange={(checked) =>
                                            setData(
                                                'is_active',
                                                Boolean(checked),
                                            )
                                        }
                                    />
                                    <Label htmlFor="is_active">Activo</Label>
                                </div>
                            </div>
                            <div className="flex flex-wrap gap-3 pt-2">
                                <Button type="submit" disabled={processing}>
                                    Guardar cambios
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link
                                        href={DepartmentController.index.url()}
                                    >
                                        Volver
                                    </Link>
                                </Button>
                                {can.delete ? (
                                    <Button
                                        type="button"
                                        variant="destructive"
                                        className="ms-auto"
                                        onClick={() =>
                                            setShowDeleteDialog(true)
                                        }
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
                title="Eliminar departamento"
                description="¿Eliminar este departamento? Solo si no tiene usuarios asignados."
                confirmLabel="Eliminar"
                variant="destructive"
                processing={deleting}
                onConfirm={handleConfirmDelete}
            />
        </AppLayout>
    );
}
