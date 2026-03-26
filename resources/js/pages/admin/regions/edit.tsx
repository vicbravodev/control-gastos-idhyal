import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import RegionController from '@/actions/App/Http/Controllers/Admin/RegionController';
import ConfirmationDialog from '@/components/confirmation-dialog';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type RegionPayload = {
    id: number;
    code: string;
    name: string;
};

export default function AdminRegionsEdit({
    region,
    can,
}: {
    region: RegionPayload;
    can: { delete: boolean };
}) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Regiones', href: RegionController.index.url() },
        {
            title: region.name,
            href: RegionController.edit.url(region.id),
        },
    ];

    const { data, setData, patch, processing, errors } = useForm({
        code: region.code,
        name: region.name,
    });

    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [deleting, setDeleting] = useState(false);

    function submit(e: FormEvent) {
        e.preventDefault();
        patch(RegionController.update.url(region.id), {
            preserveScroll: true,
        });
    }

    function handleDestroy() {
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
        router.delete(RegionController.destroy.url(region.id), {
            onFinish: () => {
                setDeleting(false);
                setShowDeleteDialog(false);
            },
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar región — ${region.name}`} />
            <div className="relative mx-auto flex w-full max-w-2xl animate-fade-in flex-col gap-4 p-4">
                <Heading
                    title="Editar región"
                    description="Actualice código o nombre. Elimine solo si no quedan estados."
                />
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base font-semibold">
                            Datos
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="flex flex-col gap-5">
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
                            </div>
                            <div className="flex flex-wrap gap-3 pt-2">
                                <Button type="submit" disabled={processing}>
                                    Guardar cambios
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href={RegionController.index.url()}>
                                        Volver
                                    </Link>
                                </Button>
                                {can.delete ? (
                                    <Button
                                        type="button"
                                        variant="destructive"
                                        className="ms-auto"
                                        onClick={handleDestroy}
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
                title="Eliminar región"
                description="¿Eliminar esta región? Solo si no tiene estados."
                confirmLabel="Eliminar"
                variant="destructive"
                processing={deleting}
                onConfirm={handleConfirmDelete}
            />
        </AppLayout>
    );
}
