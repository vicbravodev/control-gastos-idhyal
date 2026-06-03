import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import HolidayController from '@/actions/App/Http/Controllers/Admin/HolidayController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Días festivos', href: HolidayController.index.url() },
    { title: 'Nuevo', href: HolidayController.create.url() },
];

export default function AdminHolidaysCreate() {
    const form = useForm<{
        date: string;
        name: string;
        description: string;
    }>({
        date: '',
        name: '',
        description: '',
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo día festivo" />
            <div className="mx-auto flex w-full max-w-2xl animate-fade-in flex-col gap-5 p-4 sm:p-6">
                <Heading
                    title="Nuevo día festivo"
                    description="Las fechas registradas aquí se restan al calcular los días de vacaciones."
                />

                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post(HolidayController.store.url());
                    }}
                >
                    <Card>
                        <CardContent className="grid gap-4 p-5">
                            <div className="grid gap-2">
                                <Label htmlFor="date">Fecha</Label>
                                <DatePicker
                                    id="date"
                                    value={form.data.date}
                                    onChange={(v) => form.setData('date', v)}
                                />
                                <InputError message={form.errors.date} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nombre</Label>
                                <Input
                                    id="name"
                                    required
                                    placeholder="Día de la Independencia"
                                    value={form.data.name}
                                    onChange={(e) =>
                                        form.setData('name', e.target.value)
                                    }
                                />
                                <InputError message={form.errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="description">
                                    Descripción (opcional)
                                </Label>
                                <Textarea
                                    id="description"
                                    rows={2}
                                    value={form.data.description}
                                    onChange={(e) =>
                                        form.setData(
                                            'description',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError message={form.errors.description} />
                            </div>
                            <div className="flex flex-wrap gap-2 pt-2">
                                <Button
                                    type="submit"
                                    disabled={form.processing}
                                >
                                    <Save />
                                    {form.processing
                                        ? 'Guardando…'
                                        : 'Guardar festivo'}
                                </Button>
                                <Button variant="outline" type="button" asChild>
                                    <Link href={HolidayController.index.url()}>
                                        <ArrowLeft />
                                        Cancelar
                                    </Link>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </form>
            </div>
        </AppLayout>
    );
}
