import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import RoleController from '@/actions/App/Http/Controllers/Admin/RoleController';
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

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Roles', href: RoleController.index.url() },
    { title: 'Nuevo', href: RoleController.create.url() },
];

export default function AdminRolesCreate({
    permissions,
}: {
    permissions: PermissionGroup;
}) {
    const { data, setData, post, processing, errors } = useForm({
        slug: '',
        name: '',
        description: '',
        permission_ids: [] as number[],
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        post(RoleController.store.url());
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo rol" />
            <div className="relative mx-auto flex w-full max-w-3xl animate-fade-in flex-col gap-4 p-4">
                <Heading
                    title="Nuevo rol"
                    description="Cree un rol personalizado y asigne los permisos que tendrán los usuarios con ese rol."
                />
                <form onSubmit={submit} className="flex flex-col gap-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Datos</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-5 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="slug">Slug (identificador)</Label>
                                <Input
                                    id="slug"
                                    value={data.slug}
                                    onChange={(e) =>
                                        setData('slug', e.target.value)
                                    }
                                    placeholder="ej. auditor"
                                    className="font-mono"
                                    required
                                    maxLength={64}
                                />
                                <InputError message={errors.slug} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nombre</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) =>
                                        setData('name', e.target.value)
                                    }
                                    placeholder="ej. Auditor"
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
                            Crear rol
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={RoleController.index.url()}>
                                Cancelar
                            </Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
