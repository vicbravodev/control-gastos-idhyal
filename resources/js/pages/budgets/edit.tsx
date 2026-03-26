import { Head, Link, router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useEffect, useRef, useState } from 'react';
import BudgetController from '@/actions/App/Http/Controllers/Budgets/BudgetController';
import ConfirmationDialog from '@/components/confirmation-dialog';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CurrencyInput } from '@/components/ui/currency-input';
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

type Named = { id: number; name: string };
type RegionRow = { id: number; name: string | null; code: string | null };

type BudgetableTypeOpt = { value: string; label: string };

type BudgetForm = {
    id: number;
    budgetable_type: string;
    budgetable_id: number;
    period_starts_on: string | null;
    period_ends_on: string | null;
    amount_limit_cents: number;
    priority: number | null;
};

function regionLabel(r: RegionRow): string {
    return r.name ?? r.code ?? `Región #${r.id}`;
}

export default function BudgetsEdit({
    budget,
    can,
    budgetableTypes,
    users,
    roles,
    states,
    regions,
}: {
    budget: BudgetForm;
    can: { delete: boolean };
    budgetableTypes: BudgetableTypeOpt[];
    users: Named[];
    roles: Named[];
    states: Named[];
    regions: RegionRow[];
}) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Presupuestos', href: BudgetController.index.url() },
        {
            title: 'Editar',
            href: BudgetController.edit.url(budget.id),
        },
    ];

    const { data, setData, put, processing, errors, transform } = useForm({
        budgetable_type: budget.budgetable_type,
        budgetable_id: budget.budgetable_id,
        period_starts_on: budget.period_starts_on ?? '',
        period_ends_on: budget.period_ends_on ?? '',
        amount_limit_cents: budget.amount_limit_cents,
        priority: budget.priority,
    });

    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [deleting, setDeleting] = useState(false);

    function optionsForType(type: string): { id: number; label: string }[] {
        switch (type) {
            case 'user':
                return users.map((u) => ({ id: u.id, label: u.name }));
            case 'role':
                return roles.map((r) => ({ id: r.id, label: r.name }));
            case 'state':
                return states.map((s) => ({ id: s.id, label: s.name }));
            case 'region':
                return regions.map((r) => ({
                    id: r.id,
                    label: regionLabel(r),
                }));
            default:
                return [];
        }
    }

    const entityOptions = optionsForType(data.budgetable_type);

    const typeRef = useRef(budget.budgetable_type);

    useEffect(() => {
        if (typeRef.current !== data.budgetable_type) {
            typeRef.current = data.budgetable_type;
            const list = optionsForType(data.budgetable_type);
            setData('budgetable_id', list[0]?.id ?? 0);
        }
    }, [data.budgetable_type]);

    function submit(e: FormEvent) {
        e.preventDefault();
        transform((d) => ({
            ...d,
            budgetable_id: Number(d.budgetable_id),
            amount_limit_cents: Number(d.amount_limit_cents),
            priority: d.priority,
        }));
        put(BudgetController.update.url(budget.id), { preserveScroll: true });
    }

    function handleDelete() {
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
        router.delete(BudgetController.destroy.url(budget.id), {
            preserveScroll: true,
            onFinish: () => {
                setDeleting(false);
                setShowDeleteDialog(false);
            },
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar presupuesto #${budget.id}`} />
            <div className="relative mx-auto flex w-full max-w-2xl animate-fade-in flex-col gap-4 p-4">
                <div
                    className="pointer-events-none absolute inset-0 -z-10 rounded-xl opacity-[0.05] dark:opacity-[0.09]"
                    style={{
                        backgroundImage: `radial-gradient(circle at 80% 10%, currentColor 0%, transparent 40%),
                            radial-gradient(circle at 20% 90%, currentColor 0%, transparent 35%)`,
                    }}
                />
                <Heading
                    title="Editar presupuesto"
                    description="Ajusta periodo, tope o alcance. Los movimientos ya registrados en el ledger se conservan."
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
                                    <Label htmlFor="budgetable_type">
                                        Alcance
                                    </Label>
                                    <Select
                                        value={data.budgetable_type}
                                        onValueChange={(v) =>
                                            setData('budgetable_type', v)
                                        }
                                    >
                                        <SelectTrigger id="budgetable_type">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {budgetableTypes.map((t) => (
                                                <SelectItem
                                                    key={t.value}
                                                    value={t.value}
                                                >
                                                    {t.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={errors.budgetable_type}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="budgetable_id">
                                        Entidad
                                    </Label>
                                    <Select
                                        value={
                                            data.budgetable_id
                                                ? String(data.budgetable_id)
                                                : undefined
                                        }
                                        onValueChange={(v) =>
                                            setData(
                                                'budgetable_id',
                                                Number(v),
                                            )
                                        }
                                        disabled={entityOptions.length === 0}
                                        required
                                    >
                                        <SelectTrigger id="budgetable_id">
                                            <SelectValue placeholder="Selecciona…" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {entityOptions.map((o) => (
                                                <SelectItem
                                                    key={o.id}
                                                    value={String(o.id)}
                                                >
                                                    {o.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={errors.budgetable_id}
                                    />
                                </div>
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="period_starts_on">
                                        Inicio del periodo
                                    </Label>
                                    <Input
                                        id="period_starts_on"
                                        type="date"
                                        value={data.period_starts_on}
                                        onChange={(e) =>
                                            setData(
                                                'period_starts_on',
                                                e.target.value,
                                            )
                                        }
                                        required
                                    />
                                    <InputError
                                        message={errors.period_starts_on}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="period_ends_on">
                                        Fin del periodo
                                    </Label>
                                    <Input
                                        id="period_ends_on"
                                        type="date"
                                        value={data.period_ends_on}
                                        onChange={(e) =>
                                            setData(
                                                'period_ends_on',
                                                e.target.value,
                                            )
                                        }
                                        required
                                    />
                                    <InputError
                                        message={errors.period_ends_on}
                                    />
                                </div>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="amount_limit_cents">
                                    Tope del periodo (MXN)
                                </Label>
                                <CurrencyInput
                                    id="amount_limit_cents"
                                    value={data.amount_limit_cents}
                                    onChange={(cents) =>
                                        setData('amount_limit_cents', cents)
                                    }
                                    required
                                />
                                <InputError
                                    message={errors.amount_limit_cents}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="priority">
                                    Prioridad (opcional)
                                </Label>
                                <Input
                                    id="priority"
                                    type="number"
                                    min={0}
                                    max={65535}
                                    value={data.priority ?? ''}
                                    onChange={(e) =>
                                        setData(
                                            'priority',
                                            e.target.value === ''
                                                ? null
                                                : Number(e.target.value),
                                        )
                                    }
                                    placeholder="Mayor número = mayor prioridad"
                                />
                                <InputError message={errors.priority} />
                            </div>
                            <div className="flex flex-wrap gap-2 pt-2">
                                <Button type="submit" disabled={processing}>
                                    Guardar cambios
                                </Button>
                                <Button variant="outline" type="button" asChild>
                                    <Link href={BudgetController.index.url()}>
                                        Volver
                                    </Link>
                                </Button>
                                {can.delete ? (
                                    <Button
                                        variant="destructive"
                                        type="button"
                                        className="ms-auto"
                                        onClick={handleDelete}
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
                title="Eliminar presupuesto"
                description="¿Eliminar este presupuesto? Solo es posible si no tiene movimientos en el ledger."
                confirmLabel="Eliminar"
                variant="destructive"
                processing={deleting}
                onConfirm={handleConfirmDelete}
            />
        </AppLayout>
    );
}
