import { Head, Link, usePage } from '@inertiajs/react';
import { Pencil, PiggyBank, Plus } from 'lucide-react';

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
import AppLayout from '@/layouts/app-layout';
import { formatCentsMx } from '@/lib/money';
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
    can_edit: boolean;
    can_delete: boolean;
};

type Paginator = {
    data: BudgetRow[];
    links: { url: string | null; label: string; active: boolean }[];
    last_page: number;
    current_page: number;
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

export default function BudgetsIndex({
    budgets,
    can,
    filters,
}: {
    budgets: Paginator;
    can: { create: boolean };
    filters: Record<string, string>;
}) {
    const page = usePage<{ errors?: { budget?: string } }>();
    const budgetError = page.props.errors?.budget;
    const isEmpty = budgets.data.length === 0;

    const totalLimit = budgets.data.reduce(
        (acc, b) => acc + b.amount_limit_cents,
        0,
    );
    const totalCommitted = budgets.data.reduce(
        (acc, b) => acc + b.committed_cents,
        0,
    );
    const totalSpent = budgets.data.reduce((acc, b) => acc + b.spent_cents, 0);

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
                            description="No hay presupuestos registrados en el sistema."
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
                            const lowAlert = pctRemaining < 10;
                            const warnAlert = !lowAlert && pctRemaining < 30;

                            return (
                                <article
                                    key={row.id}
                                    className="flex flex-col gap-3 rounded-xl border border-border bg-card p-5"
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
                                            ampliar o pausar nuevas solicitudes.
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
        </AppLayout>
    );
}
