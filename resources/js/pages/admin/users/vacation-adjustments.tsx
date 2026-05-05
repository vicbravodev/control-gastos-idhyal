import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import StaffUserController from '@/actions/App/Http/Controllers/Admin/StaffUserController';
import UserVacationAdjustmentController from '@/actions/App/Http/Controllers/Admin/UserVacationAdjustmentController';
import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type StaffUser = { id: number; name: string; email: string };

type AdjustmentRow = {
    id: number;
    calendar_year: number;
    days: number;
    reason: string;
    granted_by: string | null;
    created_at: string | null;
};

type Balance = {
    has_hire_date: boolean;
    calendar_year: number;
    days_allocated: number;
    days_consumed: number;
    days_adjustment: number;
    days_remaining: number;
};

export default function AdminUsersVacationAdjustments({
    staffUser,
    adjustments,
    balance,
    currentYear,
}: {
    staffUser: StaffUser;
    adjustments: AdjustmentRow[];
    balance: Balance;
    currentYear: number;
}) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Usuarios', href: StaffUserController.index.url() },
        { title: staffUser.name, href: StaffUserController.edit.url(staffUser.id) },
        {
            title: 'Ajustes de vacaciones',
            href: UserVacationAdjustmentController.index.url(staffUser.id),
        },
    ];

    const { data, setData, post, processing, errors, reset } = useForm({
        calendar_year: currentYear,
        days: 0,
        reason: '',
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        post(UserVacationAdjustmentController.store.url(staffUser.id), {
            preserveScroll: true,
            onSuccess: () => {
                reset('days', 'reason');
            },
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Ajustes de vacaciones — ${staffUser.name}`} />
            <div className="relative mx-auto flex w-full max-w-4xl animate-fade-in flex-col gap-4 p-4">
                <Heading
                    title="Ajustes de vacaciones"
                    description={`Devolución, premio o corrección manual del saldo anual de ${staffUser.name}.`}
                />

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base font-semibold">
                            Saldo {balance.calendar_year}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                            <div>
                                <div className="text-muted-foreground">
                                    Asignados
                                </div>
                                <div className="text-lg font-semibold tabular-nums">
                                    {balance.days_allocated}
                                </div>
                            </div>
                            <div>
                                <div className="text-muted-foreground">
                                    Consumidos
                                </div>
                                <div className="text-lg font-semibold tabular-nums">
                                    {balance.days_consumed}
                                </div>
                            </div>
                            <div>
                                <div className="text-muted-foreground">
                                    Ajustes
                                </div>
                                <div className="text-lg font-semibold tabular-nums">
                                    {balance.days_adjustment > 0 ? '+' : ''}
                                    {balance.days_adjustment}
                                </div>
                            </div>
                            <div>
                                <div className="text-muted-foreground">
                                    Disponibles
                                </div>
                                <div className="text-lg font-semibold tabular-nums">
                                    {balance.days_remaining}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base font-semibold">
                            Registrar ajuste
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form
                            onSubmit={submit}
                            className="flex flex-col gap-5"
                        >
                            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="calendar_year">
                                        Año
                                    </Label>
                                    <Input
                                        id="calendar_year"
                                        type="number"
                                        min={currentYear - 5}
                                        max={currentYear + 1}
                                        value={data.calendar_year}
                                        onChange={(e) =>
                                            setData(
                                                'calendar_year',
                                                Number(e.target.value),
                                            )
                                        }
                                        required
                                    />
                                    <InputError
                                        message={errors.calendar_year}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="days">
                                        Días (positivo = sumar, negativo =
                                        descontar)
                                    </Label>
                                    <Input
                                        id="days"
                                        type="number"
                                        min={-60}
                                        max={60}
                                        value={data.days}
                                        onChange={(e) =>
                                            setData(
                                                'days',
                                                Number(e.target.value),
                                            )
                                        }
                                        required
                                    />
                                    <InputError message={errors.days} />
                                </div>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="reason">Motivo</Label>
                                <Input
                                    id="reason"
                                    value={data.reason}
                                    onChange={(e) =>
                                        setData('reason', e.target.value)
                                    }
                                    required
                                    maxLength={255}
                                    placeholder="Devolución de día no tomado, premio, corrección…"
                                />
                                <InputError message={errors.reason} />
                            </div>
                            <div className="flex gap-3 pt-2">
                                <Button type="submit" disabled={processing}>
                                    Registrar ajuste
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link
                                        href={StaffUserController.edit.url(
                                            staffUser.id,
                                        )}
                                    >
                                        Volver al usuario
                                    </Link>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base font-semibold">
                            Historial
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {adjustments.length === 0 ? (
                            <EmptyState
                                title="Sin ajustes registrados"
                                description="Los ajustes manuales que hagas aparecerán aquí con quién los autorizó."
                            />
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Año</TableHead>
                                        <TableHead className="text-right">
                                            Días
                                        </TableHead>
                                        <TableHead>Motivo</TableHead>
                                        <TableHead>Autorizó</TableHead>
                                        <TableHead>Fecha</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {adjustments.map((a) => (
                                        <TableRow key={a.id}>
                                            <TableCell className="tabular-nums">
                                                {a.calendar_year}
                                            </TableCell>
                                            <TableCell
                                                className={`text-right tabular-nums font-semibold ${
                                                    a.days >= 0
                                                        ? 'text-emerald-700'
                                                        : 'text-rose-700'
                                                }`}
                                            >
                                                {a.days > 0 ? '+' : ''}
                                                {a.days}
                                            </TableCell>
                                            <TableCell>{a.reason}</TableCell>
                                            <TableCell className="text-sm text-muted-foreground">
                                                {a.granted_by ?? '—'}
                                            </TableCell>
                                            <TableCell className="text-sm text-muted-foreground">
                                                {a.created_at
                                                    ? new Date(
                                                          a.created_at,
                                                      ).toLocaleString('es-MX')
                                                    : '—'}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
