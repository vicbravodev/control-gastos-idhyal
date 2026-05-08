import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import RoleController from '@/actions/App/Http/Controllers/Admin/RoleController';
import ConfirmationDialog from '@/components/confirmation-dialog';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import {
    PermissionMatrix
    
} from '@/components/permission-matrix';
import type {PermissionGroup} from '@/components/permission-matrix';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type RolePayload = {
    id: number;
    slug: string;
    name: string;
    description: string | null;
    permission_ids: number[];
};

export default function AdminRolesEdit({
    role,
    permissions,
    can,
}: {
    role: RolePayload;
    permissions: PermissionGroup;
    can: { delete: boolean };
}) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Roles', href: RoleController.index.url() },
        {
            title: role.name,
            href: RoleController.edit.url(role.id),
        },
    ];

    const { data, setData, patch, processing, errors } = useForm({
        name: role.name,
        description: role.description ?? '',
        permission_ids: role.permission_ids,
    });

    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [deleting, setDeleting] = useState(false);

    function submit(e: FormEvent) {
        e.preventDefault();
        patch(RoleController.update.url(role.id), { preserveScroll: true });
    }

    function handleConfirmDelete() {
        if (!can.delete) {
return;
}

        setDeleting(true);
        router.delete(RoleController.destroy.url(role.id), {
            onFinish: () => {
                setDeleting(false);
                setShowDeleteDialog(false);
            },
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar rol — ${role.name}`} />
            <div className="relative mx-auto flex w-full max-w-3xl animate-fade-in flex-col gap-4 p-4">
                <Heading
                    title="Editar rol"
                    description="Ajuste el nombre, descripción y permisos del rol."
                />
                <form onSubmit={submit} className="flex flex-col gap-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Datos</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-5 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="slug">
                                    Slug (no editable)
                                </Label>
                                <Input
                                    id="slug"
                                    value={role.slug}
                                    readOnly
                                    className="font-mono opacity-70"
                                />
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
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="description">
                                    Descripción
                                </Label>
                                <Textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) =>
                                        setData('description', e.target.value)
                                    }
                                    rows={2}
                                    maxLength={500}
                                />
                                <InputError message={errors.description} />
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Permisos</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <PermissionMatrix
                                permissions={permissions}
                                selectedIds={data.permission_ids}
                                onChange={(ids) =>
                                    setData('permission_ids', ids)
                                }
                            />
                            <InputError message={errors.permission_ids} />
                        </CardContent>
                    </Card>
                    <div className="flex flex-wrap gap-3">
                        <Button type="submit" disabled={processing}>
                            Guardar cambios
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={RoleController.index.url()}>
                                Volver
                            </Link>
                        </Button>
                        {can.delete ? (
                            <Button
                                type="button"
                                variant="destructive"
                                className="ms-auto"
                                onClick={() => setShowDeleteDialog(true)}
                            >
                                Eliminar
                            </Button>
                        ) : null}
                    </div>
                </form>
            </div>
            <ConfirmationDialog
                open={showDeleteDialog}
                onOpenChange={(open) => {
                    if (!open) {
setShowDeleteDialog(false);
}
                }}
                title="Eliminar rol"
                description="¿Eliminar este rol? Solo si no tiene usuarios ni políticas referenciándolo."
                confirmLabel="Eliminar"
                variant="destructive"
                processing={deleting}
                onConfirm={handleConfirmDelete}
            />
        </AppLayout>
    );
}
