import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import VacationRuleController from '@/actions/App/Http/Controllers/VacationRules/VacationRuleController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
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
    {
        title: 'Reglas de vacaciones',
        href: VacationRuleController.index.url(),
    },
    {
        title: 'Nueva',
        href: VacationRuleController.create.url(),
    },
];

export default function VacationRulesCreate() {
    const { data, setData, post, processing, errors } = useForm({
        code: '',
        name: '',
        min_years_service: '0',
        max_years_service: '',
        days_granted_per_year: '12',
        max_days_per_request: '',
        max_days_per_month: '',
        max_days_per_quarter: '',
        max_days_per_year: '',
        blackout_dates: '',
        sort_order: '0',
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        post(VacationRuleController.store.url(), { preserveScroll: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva regla de vacaciones" />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-4 p-4 animate-fade-in">
                <Heading
                    title="Nueva regla"
                    description="Define un tramo de antigüedad y los días de derecho anual."
                />
                <Card>
                    <CardHeader>
                        <CardTitle>Datos</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form
                            onSubmit={submit}
                            className="flex flex-col gap-4"
                        >
                            <div className="grid gap-2 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="code">Código</Label>
                                    <Input
                                        id="code"
                                        value={data.code}
                                        onChange={(e) =>
                                            setData('code', e.target.value)
                                        }
                                        required
                                        className="font-mono"
                                    />
                                    <InputError message={errors.code} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="sort_order">
                                        Orden de evaluación
                                    </Label>
                                    <Input
                                        id="sort_order"
                                        type="number"
                                        min={0}
                                        value={data.sort_order}
                                        onChange={(e) =>
                                            setData(
                                                'sort_order',
                                                e.target.value,
                                            )
                                        }
                                        required
                                    />
                                    <InputError message={errors.sort_order} />
                                </div>
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
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="min_years_service">
                                        Años mín. (inclusive)
                                    </Label>
                                    <Input
                                        id="min_years_service"
                                        type="number"
                                        inputMode="decimal"
                                        step="0.1"
                                        min={0}
                                        value={data.min_years_service}
                                        onChange={(e) =>
                                            setData(
                                                'min_years_service',
                                                e.target.value,
                                            )
                                        }
                                        required
                                    />
                                    <InputError
                                        message={errors.min_years_service}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="max_years_service">
                                        Años máx. (vacío = sin techo)
                                    </Label>
                                    <Input
                                        id="max_years_service"
                                        type="number"
                                        inputMode="decimal"
                                        step="0.1"
                                        min={0}
                                        value={data.max_years_service}
                                        onChange={(e) =>
                                            setData(
                                                'max_years_service',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={errors.max_years_service}
                                    />
                                </div>
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="days_granted_per_year">
                                        Días otorgados / año
                                    </Label>
                                    <Input
                                        id="days_granted_per_year"
                                        type="number"
                                        min={0}
                                        value={data.days_granted_per_year}
                                        onChange={(e) =>
                                            setData(
                                                'days_granted_per_year',
                                                e.target.value,
                                            )
                                        }
                                        required
                                    />
                                    <InputError
                                        message={errors.days_granted_per_year}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="max_days_per_request">
                                        Máx. días hábiles / solicitud
                                    </Label>
                                    <Input
                                        id="max_days_per_request"
                                        type="number"
                                        min={0}
                                        value={data.max_days_per_request}
                                        onChange={(e) =>
                                            setData(
                                                'max_days_per_request',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={errors.max_days_per_request}
                                    />
                                </div>
                            </div>
                            <div className="grid gap-4 sm:grid-cols-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="max_days_per_month">
                                        Máx. / mes
                                    </Label>
                                    <Input
                                        id="max_days_per_month"
                                        type="number"
                                        min={0}
                                        value={data.max_days_per_month}
                                        onChange={(e) =>
                                            setData(
                                                'max_days_per_month',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={errors.max_days_per_month}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="max_days_per_quarter">
                                        Máx. / trimestre
                                    </Label>
                                    <Input
                                        id="max_days_per_quarter"
                                        type="number"
                                        min={0}
                                        value={data.max_days_per_quarter}
                                        onChange={(e) =>
                                            setData(
                                                'max_days_per_quarter',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={errors.max_days_per_quarter}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="max_days_per_year">
                                        Máx. / año cal.
                                    </Label>
                                    <Input
                                        id="max_days_per_year"
                                        type="number"
                                        min={0}
                                        value={data.max_days_per_year}
                                        onChange={(e) =>
                                            setData(
                                                'max_days_per_year',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={errors.max_days_per_year}
                                    />
                                </div>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="blackout_dates">
                                    Fechas bloqueadas (JSON array, opcional)
                                </Label>
                                <Textarea
                                    id="blackout_dates"
                                    value={data.blackout_dates}
                                    onChange={(e) =>
                                        setData(
                                            'blackout_dates',
                                            e.target.value,
                                        )
                                    }
                                    rows={3}
                                    placeholder="[]"
                                    className="font-mono text-sm"
                                />
                                <InputError message={errors.blackout_dates} />
                            </div>
                            <div className="flex flex-wrap gap-3 pt-2">
                                <Button type="submit" disabled={processing}>
                                    Guardar
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href={VacationRuleController.index.url()}>
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
