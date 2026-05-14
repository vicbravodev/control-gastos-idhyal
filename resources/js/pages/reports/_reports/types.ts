export type FilterMap = {
    search: string;
    status: string;
    region_id: string;
    state_id: string;
    user_id: string;
    expense_concept_id: string;
    delivery_method: string;
    role_id: string;
    date_from: string;
    date_to: string;
};

export type FilterKey = keyof FilterMap;

export type Kpis = {
    total_count: number;
    total_requested_cents: number;
    total_approved_cents: number;
    total_paid_cents: number;
    avg_approval_hours: number | null;
    total_count_prev?: number;
    total_requested_cents_prev?: number;
    total_approved_cents_prev?: number;
    total_paid_cents_prev?: number;
    avg_approval_hours_prev?: number | null;
    total_count_delta_pct?: number | null;
    total_requested_cents_delta_pct?: number | null;
    total_approved_cents_delta_pct?: number | null;
    total_paid_cents_delta_pct?: number | null;
    avg_approval_hours_delta?: number | null;
};

export type SparklineMap = {
    count: number[];
    solicitado: number[];
    aprobado: number[];
    pagado: number[];
};

export type StatusBucket = {
    status: string;
    label: string;
    count: number;
    total_cents: number;
};

export type TimeSeriesPoint = {
    bucket: string;
    label: string;
    count: number;
    solicitado_cents: number;
    aprobado_cents: number;
    pagado_cents: number;
};

export type DimensionRow = {
    key: string;
    meta: string;
    count: number;
    solicitado_cents: number;
    aprobado_cents: number;
    pagado_cents: number;
    pend_cents: number;
};

export type Template = {
    id: number;
    slug: string | null;
    name: string;
    description: string | null;
    icon: string;
    view: 'resumen' | 'pivote' | 'detalle';
    group_by: string | null;
    filters: Record<string, unknown>;
    is_built_in: boolean;
    is_shared: boolean;
    is_owner: boolean;
};

export type PeriodPreset = {
    id: string;
    label: string;
    range_label: string;
};

export type PeriodInfo = {
    id: string;
    label: string;
    range_label: string;
    start: string;
    end: string;
    prev_start: string;
    prev_end: string;
    granularity: 'day' | 'week' | 'month';
};

export type DetailRow = {
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

export type Paginator<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

export type FilterOption = { value: string; label: string };
export type StateFilterOption = FilterOption & { region_id?: string };

export type FilterOptions = {
    statuses: FilterOption[];
    regions: FilterOption[];
    states: StateFilterOption[];
    users: FilterOption[];
    roles: FilterOption[];
    expense_concepts: FilterOption[];
    delivery_methods: FilterOption[];
};

export type ViewId = 'resumen' | 'pivote' | 'detalle';

export type GroupBy =
    | 'region'
    | 'estado'
    | 'usuario'
    | 'concepto'
    | 'status'
    | 'mes'
    | 'entrega'
    | 'rol';

export const GROUP_BY_OPTIONS: Array<{ id: GroupBy; label: string }> = [
    { id: 'region', label: 'Región' },
    { id: 'estado', label: 'Estado (geo)' },
    { id: 'usuario', label: 'Usuario' },
    { id: 'concepto', label: 'Concepto' },
    { id: 'status', label: 'Estado' },
    { id: 'mes', label: 'Mes' },
    { id: 'entrega', label: 'Forma de entrega' },
    { id: 'rol', label: 'Rol del solicitante' },
];

export type ReportsPageProps = {
    kpis: Kpis;
    sparklines: SparklineMap;
    byStatus: StatusBucket[];
    templates: Template[];
    period: PeriodInfo;
    period_presets: PeriodPreset[];
    view: ViewId;
    group_by: GroupBy;
    compare: boolean;
    filters: FilterMap;
    active_template_id: number | null;
    filter_options?: FilterOptions;

    timeSeries?: TimeSeriesPoint[];
    byRegion?: DimensionRow[];
    byConcept?: DimensionRow[];
    byUser?: DimensionRow[];

    byDimension?: DimensionRow[];

    expenseRequests?: Paginator<DetailRow>;
};
