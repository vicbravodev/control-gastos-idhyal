import { Head, Link, usePage } from '@inertiajs/react';
import { Pencil, PiggyBank } from 'lucide-react';
import BudgetController from '@/actions/App/Http/Controllers/Budgets/BudgetController';
import ExpenseRequestController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestController';
import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { PaginationNav } from '@/components/pagination-nav';
import { TableToolbar } from '@/components/table-toolbar';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
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

function usagePercent(spent: number, limit: number): number {
    if (limit <= 0) {
        return 0;
    }

    return Math.min(100, Math.round((spent / limit) * 100));
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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Presupuestos" />
            <div className="flex flex-col gap-4 p-4 animate-fade-in">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Presupuestos"
                        description="Cupo por periodo y alcance. Comprometido al aprobar; pagado al registrar pago."
                    />
                    <div className="flex flex-wrap gap-2">
                        {can.create ? (
                            <Button asChild>
                                <Link href={BudgetController.create.url()}>
                                    Nuevo presupuesto
                                </Link>
                            </Button>
                        ) : null}
                        <Button variant="outline" asChild>
                            <Link href={ExpenseRequestController.index.url()}>
                                Ir a solicitudes
                            </Link>
                        </Button>
                    </div>
                </div>
                <InputError message={budgetError} />
                <Card>
                    <CardHeader>
                        <CardTitle>Listado</CardTitle>
                    </CardHeader>
                    <div className="px-6 pb-4">
                        <TableToolbar
                            currentUrl={BudgetController.index.url()}
                            filters={filters}
                            searchPlaceholder="Buscar por alcance…"
                        />
                    </div>
                    <CardContent>
                        {budgets.data.length === 0 ? (
                            <EmptyState
                                icon={PiggyBank}
                                title="Sin presupuestos"
                                description="No hay presupuestos registrados en el sistema."
                            />
                        ) : (
                            <>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Alcance</TableHead>
                                            <TableHead>Periodo</TableHead>
                                            <TableHead className="text-right">
                                                Límite
                                            </TableHead>
                                            <TableHead className="text-right">
                                                Comprometido
                                            </TableHead>
                                            <TableHead className="text-right">
                                                Pagado
                                            </TableHead>
                                            <TableHead className="text-right">
                                                Disponible
                                            </TableHead>
                                            <TableHead className="w-[120px]">
                                                Uso
                                            </TableHead>
                                            <TableHead className="w-[72px] text-right">
                                                Editar
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {budgets.data.map((row) => {
                                            const pct = usagePercent(
                                                row.spent_cents,
                                                row.amount_limit_cents,
                                            );

                                            return (
                                                <TableRow key={row.id}>
                                                    <TableCell>
                                                        <div>
                                                            <p className="font-medium">
                                                                {
                                                                    row.scope_label
                                                                }
                                                            </p>
                                                            <p className="text-xs text-muted-foreground">
                                                                {scopeKindLabel(
                                                                    row.scope_kind,
                                                                )}
                                                                {row.priority !=
                                                                    null &&
                                                                    ` · Prioridad ${row.priority}`}
                                                            </p>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell className="text-muted-foreground">
                                                        {row.period_starts_on ??
                                                            '—'}{' '}
                                                        →{' '}
                                                        {row.period_ends_on ??
                                                            '—'}
                                                    </TableCell>
                                                    <TableCell className="text-right tabular-nums">
                                                        {formatCentsMx(
                                                            row.amount_limit_cents,
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="text-right tabular-nums">
                                                        {formatCentsMx(
                                                            row.committed_cents,
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="text-right tabular-nums">
                                                        {formatCentsMx(
                                                            row.spent_cents,
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="text-right font-medium tabular-nums">
                                                        {formatCentsMx(
                                                            row.remaining_after_spend_cents,
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex items-center gap-2">
                                                            <div className="h-2 flex-1 overflow-hidden rounded-full bg-muted">
                                                                <div
                                                                    className={`h-full rounded-full transition-all ${
                                                                        pct >= 90
                                                                            ? 'bg-destructive'
                                                                            : pct >= 70
                                                                              ? 'bg-amber-500'
                                                                              : 'bg-primary'
                                                                    }`}
                                                                    style={{
                                                                        width: `${pct}%`,
                                                                    }}
                                                                />
                                                            </div>
                                                            <span className="w-8 text-right text-xs tabular-nums text-muted-foreground">
                                                                {pct}%
                                                            </span>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell className="text-right">
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
                                                    </TableCell>
                                                </TableRow>
                                            );
                                        })}
                                    </TableBody>
                                </Table>
                                <PaginationNav
                                    links={budgets.links}
                                    currentPage={budgets.current_page}
                                    lastPage={budgets.last_page}
                                />
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
