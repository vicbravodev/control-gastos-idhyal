import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useEffect, useState } from 'react';
import RegionStatesController from '@/actions/App/Http/Controllers/Admin/RegionStatesController';
import StaffUserController from '@/actions/App/Http/Controllers/Admin/StaffUserController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
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

const NONE = '__none__';

type RegionOpt = { id: number; name: string; code: string | null };
type RoleOpt = { id: number; slug: string; name: string };
type StateOpt = { id: number; name: string; code: string | null };

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Usuarios', href: StaffUserController.index.url() },
    { title: 'Nuevo', href: StaffUserController.create.url() },
];

export default function AdminUsersCreate({
    roles,
    regions,
}: {
    roles: RoleOpt[];
    regions: RegionOpt[];
}) {
    const [stateOptions, setStateOptions] = useState<StateOpt[]>([]);

    const { data, setData, post, processing, errors, transform } = useForm({
        name: '',
        email: '',
        username: '',
        phone: '',
        password: '',
        password_confirmation: '',
        hire_date: '',
        role_id: NONE,
        region_id: NONE,
        state_id: NONE,
    });

    useEffect(() => {
        if (data.region_id === NONE) {
            setStateOptions([]);
            return;
        }

        let cancelled = false;

        fetch(RegionStatesController.url(Number(data.region_id)), {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then((r) => r.json())
            .then((body: { data: StateOpt[] }) => {
                if (!cancelled) {
                    setStateOptions(body.data ?? []);
                }
            })
            .catch(() => {
                if (!cancelled) {
                    setStateOptions([]);
                }
            });

        return () => {
            cancelled = true;
        };
    }, [data.region_id]);

    function submit(e: FormEvent) {
        e.preventDefault();
        transform((d) => ({
            name: d.name,
            email: d.email,
            username: d.username === '' ? null : d.username,
            phone: d.phone === '' ? null : d.phone,
            password: d.password,
            password_confirmation: d.password_confirmation,
            role_id: d.role_id === NONE ? null : Number(d.role_id),
            region_id: d.region_id === NONE ? null : Number(d.region_id),
            state_id: d.state_id === NONE ? null : Number(d.state_id),
            hire_date: d.hire_date === '' ? null : d.hire_date,
        }));
        post(StaffUserController.store.url(), { preserveScroll: true });
    }

    function regionLabel(r: RegionOpt): string {
        return r.name ?? r.code ?? `Región #${r.id}`;
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo usuario" />
            <div className="relative mx-auto flex w-full max-w-3xl flex-col gap-4 p-4 animate-fade-in">
                <Heading
                    title="Nuevo usuario"
                    description="Contraseña inicial obligatoria. Región y estado deben ser coherentes."
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
                                    <Label htmlFor="name">Nombre</Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) =>
                                            setData('name', e.target.value)
                                        }
                                        required
                                        autoFocus
                                    />
                                    <InputError message={errors.name} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="email">Correo</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) =>
                                            setData('email', e.target.value)
                                        }
                                        required
                                    />
                                    <InputError message={errors.email} />
                                </div>
                            </div>
                            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="username">Usuario</Label>
                                    <Input
                                        id="username"
                                        value={data.username}
                                        onChange={(e) =>
                                            setData('username', e.target.value)
                                        }
                                        autoComplete="username"
                                    />
                                    <InputError message={errors.username} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="phone">Teléfono</Label>
                                    <Input
                                        id="phone"
                                        value={data.phone}
                                        onChange={(e) =>
                                            setData('phone', e.target.value)
                                        }
                                    />
                                    <InputError message={errors.phone} />
                                </div>
                            </div>
                            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="hire_date">
                                        Fecha de ingreso
                                    </Label>
                                    <DatePicker
                                        id="hire_date"
                                        value={data.hire_date}
                                        onChange={(v) =>
                                            setData('hire_date', v)
                                        }
                                    />
                                    <InputError message={errors.hire_date} />
                                </div>
                                <div className="grid gap-2">
                                    <Label>Rol</Label>
                                    <Select
                                        value={data.role_id}
                                        onValueChange={(v) =>
                                            setData('role_id', v)
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Sin rol" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value={NONE}>
                                                Sin rol
                                            </SelectItem>
                                            {roles.map((r) => (
                                                <SelectItem
                                                    key={r.id}
                                                    value={String(r.id)}
                                                >
                                                    {r.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.role_id} />
                                </div>
                            </div>
                            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="password">
                                        Contraseña
                                    </Label>
                                    <Input
                                        id="password"
                                        type="password"
                                        value={data.password}
                                        onChange={(e) =>
                                            setData('password', e.target.value)
                                        }
                                        required
                                        autoComplete="new-password"
                                    />
                                    <InputError message={errors.password} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="password_confirmation">
                                        Confirmar contraseña
                                    </Label>
                                    <Input
                                        id="password_confirmation"
                                        type="password"
                                        value={data.password_confirmation}
                                        onChange={(e) =>
                                            setData(
                                                'password_confirmation',
                                                e.target.value,
                                            )
                                        }
                                        required
                                        autoComplete="new-password"
                                    />
                                </div>
                            </div>
                            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label>Región</Label>
                                    <Select
                                        value={data.region_id}
                                        onValueChange={(v) => {
                                            setData('region_id', v);
                                            setData('state_id', NONE);
                                        }}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Sin región" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value={NONE}>
                                                Sin región
                                            </SelectItem>
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
                                <div className="grid gap-2">
                                    <Label>Estado</Label>
                                    <Select
                                        value={data.state_id}
                                        onValueChange={(v) =>
                                            setData('state_id', v)
                                        }
                                        disabled={
                                            data.region_id === NONE ||
                                            stateOptions.length === 0
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Sin estado" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value={NONE}>
                                                Sin estado
                                            </SelectItem>
                                            {stateOptions.map((s) => (
                                                <SelectItem
                                                    key={s.id}
                                                    value={String(s.id)}
                                                >
                                                    {s.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.state_id} />
                                </div>
                            </div>
                            <div className="flex flex-wrap gap-3 pt-2">
                                <Button type="submit" disabled={processing}>
                                    Guardar
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link
                                        href={StaffUserController.index.url()}
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
