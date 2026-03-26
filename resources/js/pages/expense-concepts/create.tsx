import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import ExpenseConceptController from '@/actions/App/Http/Controllers/ExpenseConcepts/ExpenseConceptController';
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

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    {
        title: 'Conceptos de gasto',
        href: ExpenseConceptController.index.url(),
    },
    { title: 'Nuevo', href: ExpenseConceptController.create.url() },
];

export default function ExpenseConceptsCreate() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        is_active: true,
        sort_order: 0,
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        post(ExpenseConceptController.store.url(), { preserveScroll: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo concepto de gasto" />
            <div className="relative mx-auto flex w-full max-w-2xl flex-col gap-4 p-4 animate-fade-in">
                <div
                    className="pointer-events-none absolute inset-0 -z-10 rounded-xl opacity-[0.06] dark:opacity-[0.1]"
                    style={{
                        background:
                            'radial-gradient(ellipse 80% 60% at 20% 0%, currentColor, transparent 55%)',
                    }}
                />
                <Heading
                    title="Nuevo concepto"
                    description="Nombre único y orden de aparición en listas."
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
                                <Label htmlFor="name">Nombre</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) =>
                                        setData('name', e.target.value)
                                    }
                                    required
                                    autoFocus
                                    placeholder="Ej. Viáticos locales"
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
                                    Activo (visible al crear solicitudes)
                                </Label>
                            </div>
                            <InputError message={errors.is_active} />
                            <div className="flex flex-wrap gap-2 pt-2">
                                <Button type="submit" disabled={processing}>
                                    Guardar
                                </Button>
                                <Button variant="outline" type="button" asChild>
                                    <Link
                                        href={ExpenseConceptController.index.url()}
                                    >
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
