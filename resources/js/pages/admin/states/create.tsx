import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import StateController from '@/actions/App/Http/Controllers/Admin/StateController';
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

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Estados', href: StateController.index.url() },
    { title: 'Nuevo', href: StateController.create.url() },
];

function regionLabel(r: RegionOpt): string {
    return r.name ?? r.code ?? `Región #${r.id}`;
}

export default function AdminStatesCreate({
    regions,
}: {
    regions: RegionOpt[];
}) {
    const firstRegionId = regions[0]?.id;

    const { data, setData, post, processing, errors } = useForm({
        region_id: firstRegionId ?? 0,
        code: '',
        name: '',
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        post(StateController.store.url(), { preserveScroll: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo estado" />
            <div className="relative mx-auto flex w-full max-w-2xl flex-col gap-4 p-4 animate-fade-in">
                <Heading
                    title="Nuevo estado"
                    description="Asigne una región padre, código único y nombre."
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
                                <Button
                                    type="submit"
                                    disabled={processing || regions.length === 0}
                                >
                                    Guardar
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href={StateController.index.url()}>
                                        Cancelar
                                    </Link>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
