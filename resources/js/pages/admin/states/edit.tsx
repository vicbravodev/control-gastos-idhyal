import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import StateController from '@/actions/App/Http/Controllers/Admin/StateController';
import ConfirmationDialog from '@/components/confirmation-dialog';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type RegionOpt = { id: number; name: string; code: string | null };

type StatePayload = {
    id: number;
    region_id: number;
    code: string;
    name: string;
};

function regionLabel(r: RegionOpt): string {
    return r.name ?? r.code ?? `Región #${r.id}`;
}

export default function AdminStatesEdit({
    state,
    regions,
    can,
}: {
    state: StatePayload;
    regions: RegionOpt[];
    can: { delete: boolean };
}) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Estados', href: StateController.index.url() },
        {
            title: state.name,
            href: StateController.edit.url(state.id),
        },
    ];

    const { data, setData, patch, processing, errors } = useForm({
        region_id: state.region_id,
        code: state.code,
        name: state.name,
    });

    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [deleting, setDeleting] = useState(false);

    function submit(e: FormEvent) {
        e.preventDefault();
        patch(StateController.update.url(state.id), {
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
        router.delete(StateController.destroy.url(state.id), {
            onFinish: () => {
                setDeleting(false);
                setShowDeleteDialog(false);
            },
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar estado — ${state.name}`} />
            <div className="relative mx-auto flex w-full max-w-2xl animate-fade-in flex-col gap-4 p-4">
                <Heading
                    title="Editar estado"
                    description="Cambie región, código o nombre."
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
                                <Label htmlFor="region_id">Región</Label>
                                <Select
                                    value={String(data.region_id)}
                                    onValueChange={(v) =>
                                        setData('region_id', Number(v))
                                    }
                                    disabled={regions.length === 0}
                                    required
                                >
                                    <SelectTrigger id="region_id">
                                        <SelectValue placeholder="Selecciona región" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {regions.map((r) => (
                                            <SelectItem
                                                key={r.id}
                                                value={String(r.id)}
                                            >
                                                {regionLabel(r)}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.region_id} />
                            </div>
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
                                        maxLength={16}
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
                                    <Link href={StateController.index.url()}>
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
                title="Eliminar estado"
                description="¿Eliminar este estado? Se anulará la asignación en usuarios."
                confirmLabel="Eliminar"
                variant="destructive"
                processing={deleting}
                onConfirm={handleConfirmDelete}
            />
        </AppLayout>
    );
}
