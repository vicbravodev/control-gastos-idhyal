import { Form, Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useEffect, useRef, useState } from 'react';
import BudgetController from '@/actions/App/Http/Controllers/Budgets/BudgetController';
import Heading from '@/components/heading';
import { InfoAlert } from '@/components/idhyal';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CurrencyInput } from '@/components/ui/currency-input';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { formatCentsMx } from '@/lib/money';
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
    status: 'active' | 'cancelled';
    status_label: string;
    cancelled_at: string | null;
    cancelled_by_name: string | null;
    cancellation_reason: string | null;
};

type AuditEntry = {
    id: number;
    event:
        | 'created'
        | 'amount_changed'
        | 'scope_changed'
        | 'status_changed'
        | string;
    changes: Record<string, unknown>;
    reason: string | null;
    actor_name: string | null;
    created_at: string | null;
};

function regionLabel(r: RegionRow): string {
    return r.name ?? r.code ?? `Región #${r.id}`;
}

function formatDateTime(iso: string | null): string {
    if (!iso) {
        return '—';
    }

    try {
        return new Date(iso).toLocaleString('es-MX', {
            dateStyle: 'medium',
            timeStyle: 'short',
        });
    } catch {
        return iso;
    }
}

function eventLabel(event: string): string {
    switch (event) {
        case 'created':
            return 'Presupuesto creado';
        case 'amount_changed':
            return 'Cambio de monto';
        case 'scope_changed':
            return 'Cambio de alcance';
        case 'status_changed':
            return 'Cambio de estado';
        default:
            return event;
    }
}

function describeChanges(audit: AuditEntry): string | null {
    const changes = audit.changes ?? {};

    if (audit.event === 'amount_changed') {
        const from = (changes as { from?: { amount_limit_cents?: number } })
            .from?.amount_limit_cents;
        const to = (changes as { to?: { amount_limit_cents?: number } }).to
            ?.amount_limit_cents;

        if (typeof from === 'number' && typeof to === 'number') {
            return `${formatCentsMx(from)} → ${formatCentsMx(to)}`;
        }
    }

    if (audit.event === 'status_changed') {
        const from = (changes as { from?: { status?: string } }).from?.status;
        const to = (changes as { to?: { status?: string } }).to?.status;

        if (from && to) {
            return `${from} → ${to}`;
        }
    }

    if (audit.event === 'created') {
        const amount = (changes as { amount_limit_cents?: number })
            .amount_limit_cents;

        if (typeof amount === 'number') {
            return `Tope inicial: ${formatCentsMx(amount)}`;
        }
    }

    if (audit.event === 'scope_changed') {
        return 'Cambiaron los datos de alcance o periodo';
    }

    return null;
}

export default function BudgetsEdit({
    budget,
    can,
    audits,
    budgetableTypes,
    users,
    roles,
    states,
    regions,
}: {
    budget: BudgetForm;
    can: { update: boolean; cancel: boolean };
    audits: AuditEntry[];
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

    const isCancelled = budget.status === 'cancelled';

    const { data, setData, put, processing, errors, transform } = useForm({
        budgetable_type: budget.budgetable_type,
        budgetable_id: budget.budgetable_id,
        period_starts_on: budget.period_starts_on ?? '',
        period_ends_on: budget.period_ends_on ?? '',
        amount_limit_cents: budget.amount_limit_cents,
        priority: budget.priority,
    });

    const [cancelDialogOpen, setCancelDialogOpen] = useState(false);

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
                    title={`Presupuesto #${budget.id}`}
                    description={
                        isCancelled
                            ? 'Este presupuesto está cancelado y ya no es elegible para nuevas solicitudes.'
                            : 'Ajusta periodo, tope o alcance. Cada cambio queda en el historial.'
                    }
                />

                {isCancelled ? (
                    <InfoAlert tone="warning">
                        <div className="space-y-1 text-sm">
                            <p className="font-medium">
                                Cancelado el{' '}
                                {formatDateTime(budget.cancelled_at)}
                                {budget.cancelled_by_name
                                    ? ` por ${budget.cancelled_by_name}`
                                    : ''}
                                .
                            </p>
                            {budget.cancellation_reason ? (
                                <p className="text-muted-foreground">
                                    Motivo: {budget.cancellation_reason}
                                </p>
                            ) : null}
                        </div>
                    </InfoAlert>
                ) : null}

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base font-semibold">
                            Datos
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <fieldset
                            disabled={isCancelled}
                            className="contents"
                        >
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
                                            disabled={isCancelled}
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
                                            disabled={
                                                isCancelled ||
                                                entityOptions.length === 0
                                            }
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
                                            disabled={isCancelled}
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
                                            disabled={isCancelled}
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
                                            setData(
                                                'amount_limit_cents',
                                                cents,
                                            )
                                        }
                                        required
                                        disabled={isCancelled}
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
                                        disabled={isCancelled}
                                    />
                                    <InputError message={errors.priority} />
                                </div>
                                <div className="flex flex-wrap gap-2 pt-2">
                                    <Button
                                        type="submit"
                                        disabled={processing || isCancelled}
                                    >
                                        Guardar cambios
                                    </Button>
                                    <Button
                                        variant="outline"
                                        type="button"
                                        asChild
                                    >
                                        <Link
                                            href={BudgetController.index.url()}
                                        >
                                            Volver
                                        </Link>
                                    </Button>
                                    {can.cancel && !isCancelled ? (
                                        <Button
                                            variant="destructive"
                                            type="button"
                                            className="ms-auto"
                                            onClick={() =>
                                                setCancelDialogOpen(true)
                                            }
                                        >
                                            Cancelar presupuesto
                                        </Button>
                                    ) : null}
                                </div>
                            </form>
                        </fieldset>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base font-semibold">
                            Historial
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {audits.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Sin movimientos auditados todavía.
                            </p>
                        ) : (
                            <ol className="relative space-y-4 border-s border-border ps-4">
                                {audits.map((audit) => (
                                    <li
                                        key={audit.id}
                                        className="relative"
                                    >
                                        <span className="absolute -start-[21px] top-1 inline-flex size-3 items-center justify-center rounded-full border border-border bg-card">
                                            <span className="size-1.5 rounded-full bg-foreground/70" />
                                        </span>
                                        <div className="flex flex-wrap items-baseline justify-between gap-2">
                                            <p className="text-sm font-medium">
                                                {eventLabel(audit.event)}
                                            </p>
                                            <span className="text-xs text-muted-foreground">
                                                {formatDateTime(
                                                    audit.created_at,
                                                )}
                                            </span>
                                        </div>
                                        {describeChanges(audit) ? (
                                            <p className="text-sm text-muted-foreground">
                                                {describeChanges(audit)}
                                            </p>
                                        ) : null}
                                        {audit.reason ? (
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                Motivo: {audit.reason}
                                            </p>
                                        ) : null}
                                        {audit.actor_name ? (
                                            <p className="text-xs text-muted-foreground">
                                                {audit.actor_name}
                                            </p>
                                        ) : null}
                                    </li>
                                ))}
                            </ol>
                        )}
                    </CardContent>
                </Card>
            </div>

            <Dialog
                open={cancelDialogOpen}
                onOpenChange={setCancelDialogOpen}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Cancelar presupuesto</DialogTitle>
                        <DialogDescription>
                            Una vez cancelado, este presupuesto deja de ser
                            elegible para nuevas solicitudes. El historial se
                            conserva.
                        </DialogDescription>
                    </DialogHeader>
                    <Form
                        {...BudgetController.cancel.form.post({
                            budget: budget.id,
                        })}
                        onSuccess={() => setCancelDialogOpen(false)}
                        className="space-y-3"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="cancel-budget-reason">
                                        Motivo (obligatorio)
                                    </Label>
                                    <Textarea
                                        id="cancel-budget-reason"
                                        name="reason"
                                        required
                                        rows={3}
                                        minLength={3}
                                        maxLength={500}
                                        placeholder="Explica por qué se cancela este presupuesto…"
                                    />
                                    <InputError message={errors.reason} />
                                </div>
                                <DialogFooter className="gap-2">
                                    <Button
                                        variant="secondary"
                                        type="button"
                                        onClick={() =>
                                            setCancelDialogOpen(false)
                                        }
                                    >
                                        Volver
                                    </Button>
                                    <Button
                                        type="submit"
                                        variant="destructive"
                                        disabled={processing}
                                    >
                                        {processing
                                            ? 'Cancelando…'
                                            : 'Cancelar presupuesto'}
                                    </Button>
                                </DialogFooter>
                            </>
                        )}
                    </Form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
