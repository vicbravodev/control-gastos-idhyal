import { Head } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { useState } from 'react';
import VacationReportController from '@/actions/App/Http/Controllers/Admin/VacationReportController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    {
        title: 'Reporte de vacaciones',
        href: VacationReportController.index.url(),
    },
];

export default function AdminVacationReportsIndex({
    defaults,
}: {
    defaults: { from: string; to: string };
}) {
    const [from, setFrom] = useState(defaults.from);
    const [to, setTo] = useState(defaults.to);

    const exportUrl = `${VacationReportController.export.url()}?from=${from}&to=${to}`;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reporte de vacaciones" />
            <div className="mx-auto flex w-full max-w-3xl animate-fade-in flex-col gap-5 p-4 sm:p-6">
                <Heading
                    title="Reporte de vacaciones"
                    description="Exporta a Excel el personal, sus días asignados/consumidos y las solicitudes del rango seleccionado."
                />

                <Card>
                    <CardContent className="grid gap-4 p-5">
                        <div className="grid gap-2 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="from">Desde</Label>
                                <DatePicker
                                    id="from"
                                    value={from}
                                    onChange={(v) => setFrom(v)}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="to">Hasta</Label>
                                <DatePicker
                                    id="to"
                                    value={to}
                                    onChange={(v) => setTo(v)}
                                />
                            </div>
                        </div>
                        <p className="text-xs text-muted-foreground">
                            Las solicitudes incluidas son las que arrancan
                            dentro del rango. El saldo de cada empleado se
                            calcula al momento de generar el reporte.
                        </p>
                        <div className="pt-2">
                            <Button asChild disabled={!from || !to}>
                                <a href={exportUrl}>
                                    <Download />
                                    Descargar Excel
                                </a>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
