import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import RegionController from '@/actions/App/Http/Controllers/Admin/RegionController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Regiones', href: RegionController.index.url() },
    { title: 'Nueva', href: RegionController.create.url() },
];

export default function AdminRegionsCreate() {
    const { data, setData, post, processing, errors } = useForm({
        code: '',
        name: '',
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        post(RegionController.store.url(), { preserveScroll: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva región" />
            <div className="relative mx-auto flex w-full max-w-2xl flex-col gap-4 p-4 animate-fade-in">
                <Heading
                    title="Nueva región"
                    description="Código estable (p. ej. interno o clave corta) y nombre visible."
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
                                        autoFocus
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
                                    Guardar
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href={RegionController.index.url()}>
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
