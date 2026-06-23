import { Head, router } from '@inertiajs/react';
import { Info } from 'lucide-react';
import { useCallback, useMemo, useState } from 'react';

import ExpenseAnalyticsController from '@/actions/App/Http/Controllers/Reports/ExpenseAnalyticsController';
import ReportTemplateController from '@/actions/App/Http/Controllers/Reports/ReportTemplateController';
import { PageHeader } from '@/components/idhyal';
import { TabsContent } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

import { ActiveFilterChips } from './_reports/active-filter-chips';
import { AdvancedFiltersPanel } from './_reports/advanced-filters-panel';
import { DetalleView } from './_reports/detalle-view';
import { ExportDropdown } from './_reports/export-dropdown';
import { KpiStrip } from './_reports/kpi-strip';
import { PeriodToolbar } from './_reports/period-toolbar';
import { PivoteView } from './_reports/pivote-view';
import { ResumenView } from './_reports/resumen-view';
import { SaveViewDialog } from './_reports/save-view-dialog';
import { ScheduleDialog } from './_reports/schedule-dialog';
import { StatusGrid } from './_reports/status-grid';
import { TemplatesStrip } from './_reports/templates-strip';
import type {
    FilterKey,
    FilterMap,
    GroupBy,
    ReportsPageProps,
    ViewId,
} from './_reports/types';
import { ViewTabs } from './_reports/view-tabs';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Reportes de gastos', href: ExpenseAnalyticsController.index.url() },
];

type NavParams = Partial<{
    [K in FilterKey]: string;
}> & {
    period?: string;
    compare?: string;
    view?: string;
    group_by?: string;
    template_id?: string;
};

function buildParams(filters: FilterMap, extra: NavParams = {}): NavParams {
    const out: NavParams = {};
    (Object.keys(filters) as FilterKey[]).forEach((k) => {
        const v = filters[k];
        if (v) {
            out[k] = v;
        }
    });
    Object.entries(extra).forEach(([k, v]) => {
        if (v == null || v === '') {
            delete out[k as FilterKey];
        } else {
            (out as Record<string, string>)[k] = v;
        }
    });
    return out;
}

function exportHref(format: 'pdf' | 'csv', filters: FilterMap, period: string) {
    const params = new URLSearchParams();
    params.set('period', period);
    (Object.keys(filters) as FilterKey[]).forEach((k) => {
        if (filters[k]) {
            params.set(k, filters[k]);
        }
    });

    return `/reports/expenses/export/${format}?${params.toString()}`;
}

const FILTER_KEYS: FilterKey[] = [
    'search',
    'status',
    'region_id',
    'state_id',
    'user_id',
    'role_id',
    'expense_concept_id',
    'delivery_method',
    'date_from',
    'date_to',
];

export default function ReportsIndex(props: ReportsPageProps) {
    const {
        kpis,
        sparklines,
        byStatus,
        templates,
        period,
        period_presets,
        view,
        group_by,
        compare,
        filters,
        active_template_id,
        filter_options,
        timeSeries,
        byRegion,
        byConcept,
        byUser,
        byDimension,
        expenseRequests,
    } = props;

    const [showFilters, setShowFilters] = useState(false);
    const [columnsOpen, setColumnsOpen] = useState(false);
    const [saveOpen, setSaveOpen] = useState(false);
    const [scheduleOpen, setScheduleOpen] = useState(false);

    const navigate = useCallback(
        (extra: NavParams = {}, opts: { reloadOnly?: string[] } = {}) => {
            const params = buildParams(filters, extra);
            router.get(ExpenseAnalyticsController.index.url(), params, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                ...(opts.reloadOnly ? { only: opts.reloadOnly } : {}),
            });
        },
        [filters],
    );

    const onFilterChange = useCallback(
        (key: FilterKey, value: string) => {
            const extra: NavParams = { [key]: value };
            if (key === 'region_id') {
                extra.state_id = '';
            }
            navigate(extra);
        },
        [navigate],
    );

    const onPeriodChange = useCallback(
        (id: string) => {
            const extra: NavParams = { period: id };
            if (id !== 'custom') {
                extra.date_from = '';
                extra.date_to = '';
            }
            navigate(extra);
        },
        [navigate],
    );

    const onCustomRangeChange = useCallback(
        (from: string, to: string) => {
            navigate({ period: 'custom', date_from: from, date_to: to });
        },
        [navigate],
    );

    const onCompareChange = useCallback(
        (next: boolean) => navigate({ compare: next ? '1' : '' }),
        [navigate],
    );

    const onViewChange = useCallback(
        (next: ViewId) => navigate({ view: next }),
        [navigate],
    );

    const onGroupByChange = useCallback(
        (next: GroupBy) => navigate({ group_by: next }),
        [navigate],
    );

    const onClearAll = useCallback(() => {
        const empties = FILTER_KEYS.reduce(
            (acc, k) => ({ ...acc, [k]: '' }),
            {} as NavParams,
        );
        navigate({ ...empties, template_id: '' });
    }, [navigate]);

    const onSelectTemplate = useCallback(
        (id: number) => {
            navigate({ template_id: String(id) });
        },
        [navigate],
    );

    const onClearTemplate = useCallback(() => navigate({ template_id: '' }), [navigate]);

    const onDeleteTemplate = useCallback(
        (id: number) => {
            if (
                !window.confirm(
                    '¿Eliminar esta plantilla? Esta acción no se puede deshacer.',
                )
            ) {
                return;
            }

            router.delete(
                ReportTemplateController.destroy.url({ template: id }),
                {
                    preserveScroll: true,
                },
            );
        },
        [],
    );

    const toggleFiltersPanel = useCallback(() => {
        if (!filter_options && !showFilters) {
            router.reload({ only: ['filter_options'] });
        }
        setShowFilters((v) => !v);
    }, [filter_options, showFilters]);

    const onRefresh = useCallback(() => router.reload(), []);

    const activeChips = useMemo(() => {
        const chips: Array<{ key: FilterKey; label: string }> = [];
        const labelFor = (key: FilterKey, value: string): string => {
            if (!value) return '';
            const find = (
                opts: Array<{ value: string; label: string }> | undefined,
            ): string | undefined => opts?.find((o) => o.value === value)?.label;

            switch (key) {
                case 'search':
                    return `Búsqueda: “${value}”`;
                case 'status':
                    return `Estado: ${find(filter_options?.statuses) ?? value}`;
                case 'region_id':
                    return `Región: ${find(filter_options?.regions) ?? value}`;
                case 'state_id':
                    return `Estado (geo): ${find(filter_options?.states) ?? value}`;
                case 'user_id':
                    return `Usuario: ${find(filter_options?.users) ?? value}`;
                case 'role_id':
                    return `Rol: ${find(filter_options?.roles) ?? value}`;
                case 'expense_concept_id':
                    return `Concepto: ${find(filter_options?.expense_concepts) ?? value}`;
                case 'delivery_method':
                    return `Entrega: ${find(filter_options?.delivery_methods) ?? value}`;
                case 'date_from':
                    return `Desde: ${value}`;
                case 'date_to':
                    return `Hasta: ${value}`;
            }
        };

        FILTER_KEYS.forEach((k) => {
            const label = labelFor(k, filters[k]);
            if (label) chips.push({ key: k, label });
        });

        return chips;
    }, [filters, filter_options]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reportes de gastos" />
            <div className="flex animate-fade-in flex-col gap-4 p-4 sm:p-6">
                <PageHeader
                    eyebrow="Reportes"
                    title="Reportes de gastos"
                    subtitle="Constructor de reportes para contabilidad. Filtra, agrupa, compara periodos y exporta."
                    actions={
                        <div className="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                onClick={() => setSaveOpen(true)}
                                className="inline-flex items-center gap-1.5 rounded-md border border-border bg-background px-3 py-1.5 text-sm font-medium transition-colors hover:bg-accent"
                            >
                                Guardar vista
                            </button>
                            <button
                                type="button"
                                onClick={() => setScheduleOpen(true)}
                                className="inline-flex items-center gap-1.5 rounded-md border border-border bg-background px-3 py-1.5 text-sm font-medium transition-colors hover:bg-accent"
                            >
                                Programar
                            </button>
                            <ExportDropdown
                                pdfHref={exportHref('pdf', filters, period.id)}
                                csvHref={exportHref('csv', filters, period.id)}
                            />
                        </div>
                    }
                />

                <TemplatesStrip
                    templates={templates}
                    activeId={active_template_id}
                    onSelect={onSelectTemplate}
                    onClear={onClearTemplate}
                    onCreate={() => setSaveOpen(true)}
                    onDelete={onDeleteTemplate}
                />

                <PeriodToolbar
                    period={period}
                    presets={period_presets}
                    compare={compare}
                    activeFiltersCount={activeChips.length}
                    customFrom={filters.date_from}
                    customTo={filters.date_to}
                    onPeriodChange={onPeriodChange}
                    onCompareChange={onCompareChange}
                    onCustomRangeChange={onCustomRangeChange}
                    onToggleFilters={toggleFiltersPanel}
                    onRefresh={onRefresh}
                />

                <ActiveFilterChips
                    chips={activeChips}
                    onRemove={(k) => onFilterChange(k, '')}
                    onClearAll={onClearAll}
                />

                {showFilters && (
                    <AdvancedFiltersPanel
                        filters={filters}
                        options={filter_options}
                        onChange={onFilterChange}
                        onReset={onClearAll}
                        onClose={() => setShowFilters(false)}
                    />
                )}

                <KpiStrip
                    kpis={kpis}
                    sparklines={sparklines}
                    compare={compare}
                />

                <StatusGrid
                    buckets={byStatus}
                    activeStatus={filters.status}
                    onSelect={(status) => onFilterChange('status', status)}
                />

                <ViewTabs
                    view={view}
                    onViewChange={onViewChange}
                    groupBy={group_by}
                    onGroupByChange={onGroupByChange}
                    totalRows={
                        view === 'detalle' ? expenseRequests?.total : undefined
                    }
                    onToggleColumns={() => setColumnsOpen((v) => !v)}
                >
                    <TabsContent value="resumen">
                        <ResumenView
                            timeSeries={timeSeries ?? []}
                            byRegion={byRegion ?? []}
                            byConcept={byConcept ?? []}
                            byUser={byUser ?? []}
                        />
                    </TabsContent>
                    <TabsContent value="pivote">
                        <PivoteView
                            rows={byDimension ?? []}
                            groupBy={group_by}
                        />
                    </TabsContent>
                    <TabsContent value="detalle">
                        {expenseRequests ? (
                            <DetalleView
                                paginator={expenseRequests}
                                columnsOpen={columnsOpen}
                            />
                        ) : null}
                    </TabsContent>
                </ViewTabs>

                <div className="mt-2 flex items-center gap-3 rounded-md border border-dashed border-border bg-[var(--card-soft)] px-4 py-3 text-xs text-muted-foreground">
                    <Info
                        className="size-4 text-[var(--brand-blue-600)]"
                        aria-hidden
                    />
                    <span className="flex-1">
                        Los reportes se calculan en zona horaria{' '}
                        <strong>America/Mexico_City</strong>. Los montos incluyen
                        IVA cuando el concepto requiere CFDI. Última
                        actualización al cargar la página.
                    </span>
                </div>
            </div>

            <SaveViewDialog
                open={saveOpen}
                onOpenChange={setSaveOpen}
                filters={filters}
                period={period.id}
                compare={compare}
                view={view}
                groupBy={group_by}
            />
            <ScheduleDialog
                open={scheduleOpen}
                onOpenChange={setScheduleOpen}
                templates={templates}
                defaultTemplateId={active_template_id}
            />
        </AppLayout>
    );
}
