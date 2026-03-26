import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ArrowDownToLine,
    Banknote,
    CheckCircle2,
    CircleDollarSign,
    Clock,
    FileBarChart,
    Filter,
    Receipt,
    Search,
    TrendingUp,
    X,
    XCircle,
} from 'lucide-react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import ExpenseAnalyticsController from '@/actions/App/Http/Controllers/Reports/ExpenseAnalyticsController';
import ExpenseRequestController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestController';
import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import { PaginationNav } from '@/components/pagination-nav';
import { StatusBadge, getStatusLabel } from '@/components/status-badge';
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

type StatusBreakdown = {
    status: string;
    count: number;
    total_cents: number;
};

type Summary = {
    total_count: number;
    total_requested_cents: number;
    total_approved_cents: number;
    total_paid_cents: number;
    by_status: StatusBreakdown[];
};

type ListItem = {
    id: number;
    folio: string | null;
    status: string;
    requested_amount_cents: number;
    approved_amount_cents: number | null;
    paid_amount_cents: number;
    concept_label: string;
    concept_description: string | null;
    delivery_method: string;
    user_name: string;
    user_role: string | null;
    region_name: string | null;
    state_name: string | null;
    created_at: string | null;
};

type Paginator = {
    data: ListItem[];
    links: { url: string | null; label: string; active: boolean }[];
    last_page: number;
    current_page: number;
};

type FilterOption = { value: string; label: string };
type StateOption = FilterOption & { region_id: string };

type FilterOptions = {
    statuses: FilterOption[];
    regions: FilterOption[];
    states: StateOption[];
    users: FilterOption[];
    expense_concepts: FilterOption[];
    delivery_methods: FilterOption[];
};

type Filters = {
    search: string;
    status: string;
    region_id: string;
    state_id: string;
    user_id: string;
    expense_concept_id: string;
    delivery_method: string;
    date_from: string;
    date_to: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Reportes de gastos', href: ExpenseAnalyticsController.index.url() },
];

const DELIVERY_METHOD_LABELS: Record<string, string> = {
    cash: 'Efectivo',
    transfer: 'Transferencia',
};

const STATUS_CARD_CONFIG: Record<string, { icon: typeof TrendingUp; color: string }> = {
    submitted: { icon: Clock, color: 'text-blue-600 dark:text-blue-400' },
    approval_in_progress: { icon: Clock, color: 'text-amber-600 dark:text-amber-400' },
    approved: { icon: CheckCircle2, color: 'text-emerald-600 dark:text-emerald-400' },
    rejected: { icon: XCircle, color: 'text-red-600 dark:text-red-400' },
    cancelled: { icon: XCircle, color: 'text-gray-500 dark:text-gray-400' },
    pending_payment: { icon: Clock, color: 'text-orange-600 dark:text-orange-400' },
    paid: { icon: Banknote, color: 'text-emerald-600 dark:text-emerald-400' },
    closed: { icon: CheckCircle2, color: 'text-gray-600 dark:text-gray-400' },
};

const DEBOUNCE_MS = 400;

export default function ReportsIndex({
    summary,
    expenseRequests,
    filters,
    filter_options,
}: {
    summary: Summary;
    expenseRequests: Paginator;
    filters: Filters;
    filter_options?: FilterOptions;
}) {
    const [search, setSearch] = useState(filters.search);
    const [showFilters, setShowFilters] = useState(() => hasActiveFilters(filters));
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const isFirstRender = useRef(true);

    const filteredStates = useMemo(() => {
        if (!filter_options || !filters.region_id) return filter_options?.states ?? [];
        return filter_options.states.filter((s) => s.region_id === filters.region_id);
    }, [filter_options, filters.region_id]);

    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }
        if (debounceRef.current) clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(() => {
            applyFilter('search', search || undefined);
        }, DEBOUNCE_MS);
        return () => {
            if (debounceRef.current) clearTimeout(debounceRef.current);
        };
    }, [search]); // eslint-disable-line react-hooks/exhaustive-deps

    function applyFilter(key: string, value: string | undefined) {
        const params: Record<string, string> = {};
        const allKeys: (keyof Filters)[] = [
            'search', 'status', 'region_id', 'state_id', 'user_id',
            'expense_concept_id', 'delivery_method', 'date_from', 'date_to',
        ];
        for (const k of allKeys) {
            if (k === key) {
                if (value) params[k] = value;
            } else if (filters[k]) {
                if (key === 'region_id' && k === 'state_id') continue;
                params[k] = filters[k];
            }
        }
        router.get(ExpenseAnalyticsController.index.url(), params, {
            preserveState: true,
            replace: true,
            preserveScroll: true,
            only: ['expenseRequests', 'summary', 'filters'],
        });
    }

    function clearAllFilters() {
        setSearch('');
        router.get(ExpenseAnalyticsController.index.url(), {}, {
            preserveState: true,
            replace: true,
            preserveScroll: true,
        });
    }

    const handleToggleFilters = useCallback(() => {
        if (!filter_options && !showFilters) {
            router.reload({ only: ['filter_options'] });
        }
        setShowFilters((prev) => !prev);
    }, [filter_options, showFilters]);

    const exportUrl = useMemo(() => {
        const params = new URLSearchParams();
        const allKeys: (keyof Filters)[] = [
            'search', 'status', 'region_id', 'state_id', 'user_id',
            'expense_concept_id', 'delivery_method', 'date_from', 'date_to',
        ];
        for (const k of allKeys) {
            if (filters[k]) params.set(k, filters[k]);
        }
        const qs = params.toString();
        return ExpenseAnalyticsController.exportPdf.url() + (qs ? `?${qs}` : '');
    }, [filters]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reportes de gastos" />
            <div className="flex flex-col gap-6 p-4 animate-fade-in">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Reportes de gastos"
                        description="Métricas, filtros avanzados y exportación de solicitudes de gasto."
                    />
                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={handleToggleFilters}
                        >
                            <Filter className="mr-1.5 size-4" />
                            Filtros
                        </Button>
                        <Button asChild size="sm">
                            <a href={exportUrl}>
                                <ArrowDownToLine className="mr-1.5 size-4" />
                                Exportar PDF
                            </a>
                        </Button>
                    </div>
                </div>

                {/* ── KPI Summary Cards ──────────────────────────── */}
                <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                    <KpiCard
                        title="Total solicitudes"
                        value={summary.total_count.toLocaleString('es-MX')}
                        icon={Receipt}
                        className="text-primary"
                    />
                    <KpiCard
                        title="Monto solicitado"
                        value={formatCentsMx(summary.total_requested_cents)}
                        icon={CircleDollarSign}
                        className="text-blue-600 dark:text-blue-400"
                    />
                    <KpiCard
                        title="Monto aprobado"
                        value={formatCentsMx(summary.total_approved_cents)}
                        icon={CheckCircle2}
                        className="text-emerald-600 dark:text-emerald-400"
                    />
                    <KpiCard
                        title="Monto pagado"
                        value={formatCentsMx(summary.total_paid_cents)}
                        icon={Banknote}
                        className="text-green-600 dark:text-green-400"
                    />
                </div>

                {/* ── Status Breakdown Cards ─────────────────────── */}
                {summary.by_status.length > 0 && (
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                        {summary.by_status.map((s) => {
                            const cfg = STATUS_CARD_CONFIG[s.status];
                            const Icon = cfg?.icon ?? TrendingUp;
                            const color = cfg?.color ?? 'text-muted-foreground';
                            return (
                                <Card key={s.status} className="gap-2 py-3">
                                    <CardContent className="flex items-center gap-3 px-4 py-0">
                                        <Icon className={`size-5 shrink-0 ${color}`} />
                                        <div className="min-w-0">
                                            <p className="truncate text-xs text-muted-foreground">
                                                {getStatusLabel(s.status)}
                                            </p>
                                            <p className="text-lg font-semibold tabular-nums leading-tight">
                                                {s.count}
                                            </p>
                                            <p className="text-xs text-muted-foreground tabular-nums">
                                                {formatCentsMx(s.total_cents)}
                                            </p>
                                        </div>
                                    </CardContent>
                                </Card>
                            );
                        })}
                    </div>
                )}

                {/* ── Advanced Filters ───────────────────────────── */}
                {showFilters && (
                    <Card className="animate-fade-in">
                        <CardHeader className="pb-3">
                            <div className="flex items-center justify-between">
                                <CardTitle className="text-base">Filtros avanzados</CardTitle>
                                {hasActiveFilters(filters) && (
                                    <Button variant="ghost" size="sm" onClick={clearAllFilters}>
                                        <X className="mr-1 size-4" />
                                        Limpiar todo
                                    </Button>
                                )}
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                                <div className="space-y-1.5">
                                    <Label>Buscar folio</Label>
                                    <div className="relative">
                                        <Search className="pointer-events-none absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                                        <Input
                                            value={search}
                                            onChange={(e) => setSearch(e.target.value)}
                                            placeholder="Folio…"
                                            className="pl-8"
                                            autoComplete="off"
                                            spellCheck={false}
                                        />
                                    </div>
                                </div>

                                <FilterSelect
                                    label="Estado"
                                    value={filters.status}
                                    options={filter_options?.statuses ?? []}
                                    onChange={(v) => applyFilter('status', v)}
                                    allLabel="Todos los estados"
                                />

                                <FilterSelect
                                    label="Región"
                                    value={filters.region_id}
                                    options={filter_options?.regions ?? []}
                                    onChange={(v) => applyFilter('region_id', v)}
                                    allLabel="Todas las regiones"
                                />

                                <FilterSelect
                                    label="Estado (geo)"
                                    value={filters.state_id}
                                    options={filteredStates}
                                    onChange={(v) => applyFilter('state_id', v)}
                                    allLabel="Todos los estados"
                                />

                                <FilterSelect
                                    label="Usuario"
                                    value={filters.user_id}
                                    options={filter_options?.users ?? []}
                                    onChange={(v) => applyFilter('user_id', v)}
                                    allLabel="Todos los usuarios"
                                />

                                <FilterSelect
                                    label="Concepto"
                                    value={filters.expense_concept_id}
                                    options={filter_options?.expense_concepts ?? []}
                                    onChange={(v) => applyFilter('expense_concept_id', v)}
                                    allLabel="Todos los conceptos"
                                />

                                <FilterSelect
                                    label="Forma de entrega"
                                    value={filters.delivery_method}
                                    options={filter_options?.delivery_methods ?? []}
                                    onChange={(v) => applyFilter('delivery_method', v)}
                                    allLabel="Todas"
                                />

                                <div className="space-y-1.5">
                                    <Label>Desde</Label>
                                    <DatePicker
                                        value={filters.date_from}
                                        onChange={(v) => applyFilter('date_from', v || undefined)}
                                    />
                                </div>

                                <div className="space-y-1.5">
                                    <Label>Hasta</Label>
                                    <DatePicker
                                        value={filters.date_to}
                                        onChange={(v) => applyFilter('date_to', v || undefined)}
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* ── Data Table ─────────────────────────────────── */}
                <Card>
                    <CardHeader>
                        <CardTitle>Solicitudes de gasto</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {expenseRequests.data.length === 0 ? (
                            <EmptyState
                                icon={FileBarChart}
                                title="Sin resultados"
                                description="No se encontraron solicitudes con los filtros seleccionados."
                            />
                        ) : (
                            <>
                                <div className="overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Folio</TableHead>
                                                <TableHead>Usuario</TableHead>
                                                <TableHead>Región</TableHead>
                                                <TableHead>Estado (geo)</TableHead>
                                                <TableHead>Concepto</TableHead>
                                                <TableHead>Estado</TableHead>
                                                <TableHead>Entrega</TableHead>
                                                <TableHead className="text-right">Solicitado</TableHead>
                                                <TableHead className="text-right">Aprobado</TableHead>
                                                <TableHead className="text-right">Pagado</TableHead>
                                                <TableHead className="text-right">Fecha</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {expenseRequests.data.map((row) => (
                                                <TableRow key={row.id} className="group">
                                                    <TableCell>
                                                        <Link
                                                            href={ExpenseRequestController.show.url(row.id)}
                                                            className="font-medium text-primary underline-offset-4 group-hover:underline"
                                                        >
                                                            {row.folio ?? `#${row.id}`}
                                                        </Link>
                                                    </TableCell>
                                                    <TableCell>
                                                        <span className="text-sm">{row.user_name}</span>
                                                        {row.user_role && (
                                                            <span className="block text-xs text-muted-foreground">
                                                                {row.user_role}
                                                            </span>
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="text-sm">{row.region_name ?? '—'}</TableCell>
                                                    <TableCell className="text-sm">{row.state_name ?? '—'}</TableCell>
                                                    <TableCell className="max-w-[200px]">
                                                        <span className="line-clamp-1 text-sm font-medium">
                                                            {row.concept_label}
                                                        </span>
                                                        {row.concept_description && (
                                                            <span className="line-clamp-1 text-xs text-muted-foreground">
                                                                {row.concept_description}
                                                            </span>
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        <StatusBadge status={row.status} />
                                                    </TableCell>
                                                    <TableCell className="text-sm">
                                                        {DELIVERY_METHOD_LABELS[row.delivery_method] ?? row.delivery_method}
                                                    </TableCell>
                                                    <TableCell className="text-right font-medium tabular-nums">
                                                        {formatCentsMx(row.requested_amount_cents)}
                                                    </TableCell>
                                                    <TableCell className="text-right tabular-nums">
                                                        {row.approved_amount_cents !== null
                                                            ? formatCentsMx(row.approved_amount_cents)
                                                            : '—'}
                                                    </TableCell>
                                                    <TableCell className="text-right tabular-nums">
                                                        {row.paid_amount_cents > 0
                                                            ? formatCentsMx(row.paid_amount_cents)
                                                            : '—'}
                                                    </TableCell>
                                                    <TableCell className="text-right text-sm text-muted-foreground whitespace-nowrap">
                                                        {row.created_at
                                                            ? new Date(row.created_at).toLocaleDateString('es-MX', {
                                                                  day: '2-digit',
                                                                  month: 'short',
                                                                  year: 'numeric',
                                                              })
                                                            : '—'}
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                                <PaginationNav
                                    links={expenseRequests.links}
                                    currentPage={expenseRequests.current_page}
                                    lastPage={expenseRequests.last_page}
                                />
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

/* ── Helper components ──────────────────────────── */

function KpiCard({
    title,
    value,
    icon: Icon,
    className,
}: {
    title: string;
    value: string;
    icon: typeof TrendingUp;
    className?: string;
}) {
    return (
        <Card className="gap-2 py-4">
            <CardContent className="flex items-center gap-3 px-5 py-0">
                <div className="rounded-lg bg-muted p-2.5">
                    <Icon className={`size-5 ${className ?? ''}`} />
                </div>
                <div className="min-w-0">
                    <p className="truncate text-xs text-muted-foreground">{title}</p>
                    <p className="text-lg font-bold tabular-nums leading-tight">{value}</p>
                </div>
            </CardContent>
        </Card>
    );
}

function FilterSelect({
    label,
    value,
    options,
    onChange,
    allLabel,
}: {
    label: string;
    value: string;
    options: FilterOption[];
    onChange: (v: string | undefined) => void;
    allLabel: string;
}) {
    return (
        <div className="space-y-1.5">
            <Label>{label}</Label>
            <Select
                value={value || '__all__'}
                onValueChange={(v) => onChange(v === '__all__' ? undefined : v)}
            >
                <SelectTrigger>
                    <SelectValue placeholder={label} />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="__all__">{allLabel}</SelectItem>
                    {options.map((opt) => (
                        <SelectItem key={opt.value} value={opt.value}>
                            {opt.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}

function hasActiveFilters(filters: Filters): boolean {
    return Object.values(filters).some((v) => v !== '');
}
