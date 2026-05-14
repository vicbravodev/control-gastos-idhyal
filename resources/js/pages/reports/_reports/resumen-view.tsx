import { Layers, Map, TrendingUp, Users } from 'lucide-react';
import {
    CartesianGrid,
    Cell,
    Legend,
    Line,
    LineChart,
    Pie,
    PieChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

import { DistributionBar } from '@/components/idhyal';
import { formatCentsMx } from '@/lib/money';

import type { DimensionRow, TimeSeriesPoint } from './types';

const TREND_COLORS = {
    solicitado: 'var(--brand-blue-500)',
    aprobado: 'var(--brand-gold-500)',
    pagado: 'oklch(0.62 0.11 200)',
};

const DONUT_COLORS = [
    'var(--brand-blue-500)',
    'var(--brand-gold-500)',
    'oklch(0.62 0.11 200)',
    'oklch(0.55 0.14 320)',
    'oklch(0.70 0.10 145)',
];

type Props = {
    timeSeries: TimeSeriesPoint[];
    byRegion: DimensionRow[];
    byConcept: DimensionRow[];
    byUser: DimensionRow[];
};

function fmtNumber(n: number): string {
    return new Intl.NumberFormat('es-MX').format(n);
}

function tooltipFormatter(value: unknown, name: unknown): [string, string] {
    const num = typeof value === 'number' ? value : Number(value ?? 0);
    const label = String(name ?? '');
    if (label === 'count') {
        return [fmtNumber(num), 'Solicitudes'];
    }
    return [formatCentsMx(num), label];
}

export function ResumenView({ timeSeries, byRegion, byConcept, byUser }: Props) {
    const trendData = timeSeries.map((p) => ({
        label: p.label,
        Solicitado: p.solicitado_cents,
        Aprobado: p.aprobado_cents,
        Pagado: p.pagado_cents,
    }));

    const maxRegion = byRegion.reduce((max, r) => Math.max(max, r.solicitado_cents), 1);
    const conceptTotal = byConcept.reduce((s, c) => s + c.solicitado_cents, 0);
    const topConcepts = byConcept.slice(0, 5);
    const donutData = topConcepts.map((c, i) => ({
        name: c.key,
        value: c.solicitado_cents,
        color: DONUT_COLORS[i % DONUT_COLORS.length],
    }));

    const maxUser = byUser.reduce((max, u) => Math.max(max, u.solicitado_cents), 1);

    return (
        <div className="flex flex-col gap-4">
            {/* Trend chart — full width */}
            <Card icon={<TrendingUp className="size-4 text-[var(--brand-blue-600)]" />} title="Evolución mensual">
                <div className="h-[260px] p-4">
                    <ResponsiveContainer width="100%" height="100%">
                        <LineChart data={trendData} margin={{ left: 8, right: 16, top: 8, bottom: 0 }}>
                            <CartesianGrid strokeDasharray="3 3" stroke="var(--border)" vertical={false} />
                            <XAxis
                                dataKey="label"
                                tick={{ fill: 'var(--muted-foreground)', fontSize: 11 }}
                                tickLine={false}
                                axisLine={{ stroke: 'var(--border)' }}
                            />
                            <YAxis
                                tick={{ fill: 'var(--muted-foreground)', fontSize: 11 }}
                                tickLine={false}
                                axisLine={false}
                                tickFormatter={(v) =>
                                    v >= 1_000_000
                                        ? `$${(v / 1_000_000).toFixed(1)}M`
                                        : v >= 1_000
                                          ? `$${Math.round(v / 1_000)}k`
                                          : `$${v}`
                                }
                            />
                            <Tooltip
                                formatter={tooltipFormatter}
                                contentStyle={{
                                    background: 'var(--background)',
                                    border: '1px solid var(--border)',
                                    borderRadius: 8,
                                    fontSize: 12,
                                }}
                            />
                            <Legend
                                iconType="circle"
                                wrapperStyle={{ fontSize: 12, paddingTop: 8 }}
                            />
                            <Line
                                type="monotone"
                                dataKey="Solicitado"
                                stroke={TREND_COLORS.solicitado}
                                strokeWidth={2}
                                dot={{ r: 3 }}
                                activeDot={{ r: 5 }}
                                isAnimationActive={false}
                            />
                            <Line
                                type="monotone"
                                dataKey="Aprobado"
                                stroke={TREND_COLORS.aprobado}
                                strokeWidth={2}
                                dot={{ r: 3 }}
                                activeDot={{ r: 5 }}
                                isAnimationActive={false}
                            />
                            <Line
                                type="monotone"
                                dataKey="Pagado"
                                stroke={TREND_COLORS.pagado}
                                strokeWidth={2}
                                dot={{ r: 3 }}
                                activeDot={{ r: 5 }}
                                isAnimationActive={false}
                            />
                        </LineChart>
                    </ResponsiveContainer>
                </div>
            </Card>

            <div className="grid gap-4 lg:grid-cols-2">
                <Card icon={<Map className="size-4 text-[var(--brand-blue-600)]" />} title="Por región">
                    <table className="w-full text-sm">
                        <thead className="border-b border-border bg-[var(--card-soft)] text-[11px] uppercase tracking-wider text-muted-foreground">
                            <tr>
                                <th className="px-4 py-2 text-left font-semibold">Región</th>
                                <th className="px-4 py-2 text-right font-semibold">Sol.</th>
                                <th className="px-4 py-2 text-right font-semibold">Total</th>
                                <th className="px-4 py-2 text-left font-semibold">Distribución</th>
                            </tr>
                        </thead>
                        <tbody>
                            {byRegion.length === 0 ? (
                                <tr>
                                    <td colSpan={4} className="px-4 py-6 text-center text-sm text-muted-foreground">
                                        Sin datos en este periodo
                                    </td>
                                </tr>
                            ) : (
                                byRegion.map((r) => (
                                    <tr key={r.key} className="border-t border-border">
                                        <td className="px-4 py-2.5 font-semibold">{r.key}</td>
                                        <td className="px-4 py-2.5 text-right tabular-nums">{fmtNumber(r.count)}</td>
                                        <td className="px-4 py-2.5 text-right font-semibold tabular-nums">
                                            {formatCentsMx(r.solicitado_cents)}
                                        </td>
                                        <td className="w-[140px] px-4 py-2.5">
                                            <DistributionBar value={r.solicitado_cents} max={maxRegion} />
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </Card>

                <Card icon={<Layers className="size-4 text-[var(--brand-blue-600)]" />} title="Por concepto">
                    {topConcepts.length === 0 ? (
                        <div className="p-6 text-center text-sm text-muted-foreground">
                            Sin datos en este periodo
                        </div>
                    ) : (
                        <div className="flex items-center gap-4 p-4">
                            <div className="h-[160px] w-[160px] shrink-0">
                                <ResponsiveContainer width="100%" height="100%">
                                    <PieChart>
                                        <Pie
                                            data={donutData}
                                            innerRadius={45}
                                            outerRadius={70}
                                            paddingAngle={2}
                                            dataKey="value"
                                            stroke="var(--background)"
                                            strokeWidth={2}
                                        >
                                            {donutData.map((entry, idx) => (
                                                <Cell key={idx} fill={entry.color} />
                                            ))}
                                        </Pie>
                                    </PieChart>
                                </ResponsiveContainer>
                            </div>
                            <ul className="flex min-w-0 flex-1 flex-col gap-2">
                                {topConcepts.map((c, i) => (
                                    <li key={c.key} className="flex items-center gap-2.5">
                                        <span
                                            className="size-2.5 shrink-0 rounded-sm"
                                            style={{
                                                background: DONUT_COLORS[i % DONUT_COLORS.length],
                                            }}
                                        />
                                        <div className="min-w-0 flex-1">
                                            <div className="truncate text-sm font-medium">
                                                {c.key}
                                            </div>
                                            <div className="text-[11px] text-muted-foreground">
                                                {fmtNumber(c.count)} sol ·{' '}
                                                {formatCentsMx(c.solicitado_cents)}
                                            </div>
                                        </div>
                                        <div className="t-num text-xs font-semibold tabular-nums">
                                            {conceptTotal > 0
                                                ? Math.round((c.solicitado_cents / conceptTotal) * 100)
                                                : 0}
                                            %
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}
                </Card>
            </div>

            <Card icon={<Users className="size-4 text-[var(--brand-blue-600)]" />} title="Top solicitantes">
                <table className="w-full text-sm">
                    <thead className="border-b border-border bg-[var(--card-soft)] text-[11px] uppercase tracking-wider text-muted-foreground">
                        <tr>
                            <th className="px-4 py-2 text-left font-semibold">Usuario</th>
                            <th className="px-4 py-2 text-left font-semibold">Rol · Región</th>
                            <th className="px-4 py-2 text-right font-semibold">Solicitudes</th>
                            <th className="px-4 py-2 text-right font-semibold">Total solicitado</th>
                            <th className="px-4 py-2 text-right font-semibold">Pendiente</th>
                            <th className="px-4 py-2 text-left font-semibold">Distribución</th>
                        </tr>
                    </thead>
                    <tbody>
                        {byUser.length === 0 ? (
                            <tr>
                                <td colSpan={6} className="px-4 py-6 text-center text-sm text-muted-foreground">
                                    Sin datos en este periodo
                                </td>
                            </tr>
                        ) : (
                            byUser.map((u) => (
                                <tr key={u.key} className="border-t border-border">
                                    <td className="px-4 py-2.5 font-semibold">{u.key}</td>
                                    <td className="px-4 py-2.5 text-xs text-muted-foreground">
                                        {u.meta}
                                    </td>
                                    <td className="px-4 py-2.5 text-right tabular-nums">
                                        {fmtNumber(u.count)}
                                    </td>
                                    <td className="px-4 py-2.5 text-right font-semibold tabular-nums">
                                        {formatCentsMx(u.solicitado_cents)}
                                    </td>
                                    <td
                                        className={`px-4 py-2.5 text-right tabular-nums ${
                                            u.pend_cents > 0
                                                ? 'text-[var(--warning-fg)]'
                                                : 'text-muted-foreground'
                                        }`}
                                    >
                                        {u.pend_cents > 0
                                            ? formatCentsMx(u.pend_cents)
                                            : '—'}
                                    </td>
                                    <td className="w-[160px] px-4 py-2.5">
                                        <DistributionBar value={u.solicitado_cents} max={maxUser} />
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </Card>
        </div>
    );
}

function Card({
    icon,
    title,
    children,
    action,
}: {
    icon: React.ReactNode;
    title: string;
    children: React.ReactNode;
    action?: React.ReactNode;
}) {
    return (
        <section className="overflow-hidden rounded-xl border border-border bg-card">
            <header className="flex items-center gap-2.5 border-b border-border px-5 py-3">
                {icon}
                <h2 className="text-sm font-semibold">{title}</h2>
                {action ? <div className="ml-auto">{action}</div> : null}
            </header>
            {children}
        </section>
    );
}

