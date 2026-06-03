import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import { Pencil, PiggyBank, Plus, XCircle } from 'lucide-react';
import { useState } from 'react';

import BudgetController from '@/actions/App/Http/Controllers/Budgets/BudgetController';
import ExpenseRequestController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestController';
import { EmptyState } from '@/components/empty-state';
import {
    BudgetGauge,
    InfoAlert,
    PageHeader,
    StatCard,
} from '@/components/idhyal';
import InputError from '@/components/input-error';
import { PaginationNav } from '@/components/pagination-nav';
import { TableToolbar } from '@/components/table-toolbar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { formatCentsMx } from '@/lib/money';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type BudgetRow = {
    id: number;
    period_starts_on: string | null;
    period_ends_on: string | null;
    amount_limit_cents: number;
    priority: number | null;
    scope_kind: string;
    scope_label: string;
    committed_cents: number;
    spent_cents: number;
    remaining_after_spend_cents: number;
    status: 'active' | 'cancelled';
    status_label: string;
    can_edit: boolean;
    can_cancel: boolean;
};

type Paginator = {
    data: BudgetRow[];
    links: { url: string | null; label: string; active: boolean }[];
    last_page: number;
    current_page: number;
};

type Filters = {
    search: string;
    status: 'active' | 'cancelled' | 'all';
};

function scopeKindLabel(kind: string): string {
    switch (kind) {
        case 'user':
            return 'Usuario';
        case 'role':
            return 'Rol';
        case 'state':
            return 'Estado';
        case 'region':
            return 'Región';
        default:
            return kind;
    }
}

function formatPeriod(starts: string | null, ends: string | null): string {
    if (!starts && !ends) {
        return 'Periodo abierto';
    }

    return `${starts ?? '—'} → ${ends ?? '—'}`;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Presupuestos', href: BudgetController.index.url() },
];

const STATUS_TABS: { value: Filters['status']; label: string }[] = [
    { value: 'active', label: 'Activos' },
    { value: 'cancelled', label: 'Cancelados' },
    { value: 'all', label: 'Todos' },
];

export default function BudgetsIndex({
    budgets,
    can,
    filters,
}: {
    budgets: Paginator;
    can: { create: boolean };
    filters: Filters;
}) {
    const page = usePage<{ errors?: { budget?: string } }>();
    const budgetError = page.props.errors?.budget;
    const isEmpty = budgets.data.length === 0;
    const [cancelTarget, setCancelTarget] = useState<BudgetRow | null>(null);

    const totalLimit = budgets.data.reduce(
        (acc, b) => acc + b.amount_limit_cents,
        0,
    );
    const totalCommitted = budgets.data.reduce(
        (acc, b) => acc + b.committed_cents,
        0,
    );
    const totalSpent = budgets.data.reduce((acc, b) => acc + b.spent_cents, 0);

    function setStatusFilter(value: Filters['status']) {
        const params: Record<string, string> = {};

        if (filters.search) {
            params.search = filters.search;
        }

        if (value !== 'active') {
            params.status = value;
        }

        router.get(BudgetController.index.url(), params, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Presupuestos" />
            <div className="flex animate-fade-in flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    eyebrow="Administración"
                    title="Presupuestos"
                    subtitle="Cupo por periodo y alcance. Comprometido al aprobar; pagado al registrar pago."
                    actions={
                        <>
                            <Button variant="outline" asChild>
                                <Link
                                    href={ExpenseRequestController.index.url()}
                                >
                                    Ir a solicitudes
                                </Link>
                            </Button>
                            {can.create ? (
                                <Button asChild>
                                    <Link href={BudgetController.create.url()}>
                                        <Plus />
                                        Nuevo presupuesto
                                    </Link>
                                </Button>
                            ) : null}
                        </>
                    }
                />

                {budgetError ? (
                    <InfoAlert tone="danger">
                        <InputError message={budgetError} />
                    </InfoAlert>
                ) : null}

                {!isEmpty ? (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <StatCard
                            label="Total asignado"
                            value={formatCentsMx(totalLimit)}
                            hint={`${budgets.data.length} presupuestos visibles`}
                        />
                        <StatCard
                            label="Comprometido"
                            value={formatCentsMx(totalCommitted)}
                            iconTone="gold"
                        />
                        <StatCard
                            label="Pagado"
                            value={formatCentsMx(totalSpent)}
                        />
                    </div>
                ) : null}

                <div className="flex flex-wrap items-center gap-2">
                    <div
                        role="tablist"
                        aria-label="Filtrar por estado"
                        className="inline-flex rounded-lg border border-border bg-card p-1"
                    >
                        {STATUS_TABS.map((tab) => {
                            const active = filters.status === tab.value;

                            return (
                                <button
                                    key={tab.value}
                                    type="button"
                                    role="tab"
                                    aria-selected={active}
                                    onClick={() => setStatusFilter(tab.value)}
                                    className={cn(
                                        'rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                                        active
                                            ? 'bg-muted text-foreground'
                                            : 'text-muted-foreground hover:bg-accent/40 hover:text-foreground',
                                    )}
                                >
                                    {tab.label}
                                </button>
                            );
                        })}
                    </div>
                </div>

                <div className="rounded-xl border border-border bg-card p-3.5">
                    <TableToolbar
                        currentUrl={BudgetController.index.url()}
                        filters={filters}
                        searchPlaceholder="Buscar por alcance…"
                    />
                </div>

                {isEmpty ? (
                    <div className="overflow-hidden rounded-xl border border-border bg-card">
                        <EmptyState
                            icon={PiggyBank}
                            title="Sin presupuestos"
                            description={
                                filters.status === 'cancelled'
                                    ? 'No hay presupuestos cancelados.'
                                    : 'No hay presupuestos registrados con este filtro.'
                            }
                        />
                    </div>
                ) : (
                    <div className="grid gap-4 lg:grid-cols-2">
                        {budgets.data.map((row) => {
                            const pctRemaining =
                                row.amount_limit_cents > 0
                                    ? (row.remaining_after_spend_cents /
                                          row.amount_limit_cents) *
                                      100
                                    : 0;
                            const isCancelled = row.status === 'cancelled';
                            const lowAlert =
                                !isCancelled && pctRemaining < 10;
                            const warnAlert =
                                !isCancelled &&
                                !lowAlert &&
                                pctRemaining < 30;

                            return (
                                <article
                                    key={row.id}
                                    className={cn(
                                        'flex flex-col gap-3 rounded-xl border bg-card p-5',
                                        isCancelled
                                            ? 'border-border/60 opacity-80'
                                            : 'border-border',
                                    )}
                                >
                                    <header className="flex flex-wrap items-start justify-between gap-2">
                                        <div className="min-w-0">
                                            <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                                <Badge
                                                    variant="secondary"
                                                    className="bg-[var(--brand-blue-50)] text-[var(--brand-blue-700)]"
                                                >
                                                    {scopeKindLabel(
                                                        row.scope_kind,
                                                    )}
                                                </Badge>
                                                <Badge
                                                    variant="secondary"
                                                    className={cn(
                                                        isCancelled
                                                            ? 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200'
                                                            : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
                                                    )}
                                                >
                                                    {row.status_label}
                                                </Badge>
                                                {row.priority != null ? (
                                                    <span>
                                                        Prioridad {row.priority}
                                                    </span>
                                                ) : null}
                                            </div>
                                            <h3 className="mt-1 text-base font-semibold tracking-[-0.01em]">
                                                {row.scope_label}
                                            </h3>
                                            <p className="text-xs text-muted-foreground">
                                                {formatPeriod(
                                                    row.period_starts_on,
                                                    row.period_ends_on,
                                                )}
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-1">
                                            {row.can_edit ? (
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    asChild
                                                >
                                                    <Link
                                                        href={BudgetController.edit.url(
                                                            row.id,
                                                        )}
                                                    >
                                                        <Pencil className="size-4" />
                                                        <span className="sr-only">
                                                            Editar
                                                        </span>
                                                    </Link>
                                                </Button>
                                            ) : null}
                                            {row.can_cancel ? (
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() =>
                                                        setCancelTarget(row)
                                                    }
                                                    className="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                                >
                                                    <XCircle className="size-4" />
                                                    <span className="sr-only">
                                                        Cancelar
                                                    </span>
                                                </Button>
                                            ) : null}
                                        </div>
                                    </header>
                                    <div>
                                        <div className="flex items-baseline justify-between text-xs text-muted-foreground">
                                            <span>Asignado</span>
                                            <span className="t-num text-base font-semibold text-foreground">
                                                {formatCentsMx(
                                                    row.amount_limit_cents,
                                                )}
                                            </span>
                                        </div>
                                        <div className="mt-3">
                                            <BudgetGauge
                                                assignedCents={
                                                    row.amount_limit_cents
                                                }
                                                committedCents={
                                                    row.committed_cents
                                                }
                                                spentCents={row.spent_cents}
                                            />
                                        </div>
                                    </div>
                                    {lowAlert ? (
                                        <InfoAlert tone="danger">
                                            Disponible &lt; 10%. Considera
                                            ampliar o cancelar este presupuesto.
                                        </InfoAlert>
                                    ) : warnAlert ? (
                                        <InfoAlert tone="warning">
                                            Disponible &lt; 30%. Vigila el
                                            consumo.
                                        </InfoAlert>
                                    ) : null}
                                </article>
                            );
                        })}
                    </div>
                )}

                {!isEmpty ? (
                    <PaginationNav
                        links={budgets.links}
                        currentPage={budgets.current_page}
                        lastPage={budgets.last_page}
                    />
                ) : null}
            </div>

            <CancelBudgetDialog
                target={cancelTarget}
                onClose={() => setCancelTarget(null)}
            />
        </AppLayout>
    );
}

function CancelBudgetDialog({
    target,
    onClose,
}: {
    target: BudgetRow | null;
    onClose: () => void;
}) {
    return (
        <Dialog
            open={target !== null}
            onOpenChange={(open) => {
                if (!open) {
onClose();
}
            }}
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Cancelar presupuesto</DialogTitle>
                    <DialogDescription>
                        Una vez cancelado, este presupuesto deja de ser elegible
                        para nuevas solicitudes. El historial se conserva.
                    </DialogDescription>
                </DialogHeader>
                {target ? (
                    <Form
                        {...BudgetController.cancel.form.post({
                            budget: target.id,
                        })}
                        onSuccess={onClose}
                        className="space-y-3"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="text-sm">
                                    <p className="font-medium">
                                        {target.scope_label}
                                    </p>
                                    <p className="text-muted-foreground">
                                        {formatPeriod(
                                            target.period_starts_on,
                                            target.period_ends_on,
                                        )}{' '}
                                        · {formatCentsMx(target.amount_limit_cents)}
                                    </p>
                                </div>
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
                                        onClick={onClose}
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
                ) : null}
            </DialogContent>
        </Dialog>
    );
}
