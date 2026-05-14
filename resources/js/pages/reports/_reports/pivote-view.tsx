import { DistributionBar } from '@/components/idhyal';
import { formatCentsMx } from '@/lib/money';

import { GROUP_BY_OPTIONS, type DimensionRow, type GroupBy } from './types';

type Props = {
    rows: DimensionRow[];
    groupBy: GroupBy;
};

function fmtNumber(n: number): string {
    return new Intl.NumberFormat('es-MX').format(n);
}

export function PivoteView({ rows, groupBy }: Props) {
    const groupLabel =
        GROUP_BY_OPTIONS.find((g) => g.id === groupBy)?.label ?? 'Dimensión';

    const totals = rows.reduce(
        (acc, r) => ({
            count: acc.count + r.count,
            solicitado: acc.solicitado + r.solicitado_cents,
            aprobado: acc.aprobado + r.aprobado_cents,
            pagado: acc.pagado + r.pagado_cents,
            pend: acc.pend + r.pend_cents,
        }),
        { count: 0, solicitado: 0, aprobado: 0, pagado: 0, pend: 0 },
    );

    const maxSol = rows.reduce((m, r) => Math.max(m, r.solicitado_cents), 0);

    return (
        <section className="overflow-hidden rounded-xl border border-border bg-card">
            <table className="w-full text-sm">
                <thead className="border-b border-border bg-[var(--card-soft)] text-[11px] uppercase tracking-wider text-muted-foreground">
                    <tr>
                        <th className="px-4 py-2.5 text-left font-semibold w-[28%]">
                            {groupLabel}
                        </th>
                        <th className="px-4 py-2.5 text-right font-semibold">
                            Solicitudes
                        </th>
                        <th className="px-4 py-2.5 text-right font-semibold">
                            Solicitado
                        </th>
                        <th className="px-4 py-2.5 text-right font-semibold">
                            Aprobado
                        </th>
                        <th className="px-4 py-2.5 text-right font-semibold">
                            Pendiente
                        </th>
                        <th className="w-[90px] px-4 py-2.5 text-right font-semibold">
                            % tot.
                        </th>
                        <th className="w-[160px] px-4 py-2.5 text-left font-semibold">
                            Distribución
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {rows.length === 0 ? (
                        <tr>
                            <td
                                colSpan={7}
                                className="px-4 py-10 text-center text-sm text-muted-foreground"
                            >
                                Sin datos en este periodo
                            </td>
                        </tr>
                    ) : (
                        rows.map((r) => (
                            <tr key={r.key} className="border-t border-border">
                                <td className="px-4 py-2.5">
                                    <div className="font-semibold">{r.key}</div>
                                    <div className="text-xs text-muted-foreground">
                                        {r.meta}
                                    </div>
                                </td>
                                <td className="px-4 py-2.5 text-right tabular-nums">
                                    {fmtNumber(r.count)}
                                </td>
                                <td className="px-4 py-2.5 text-right font-semibold tabular-nums">
                                    {formatCentsMx(r.solicitado_cents)}
                                </td>
                                <td className="px-4 py-2.5 text-right tabular-nums">
                                    {formatCentsMx(r.aprobado_cents)}
                                </td>
                                <td
                                    className={`px-4 py-2.5 text-right tabular-nums ${
                                        r.pend_cents > 0
                                            ? 'text-[var(--warning-fg)]'
                                            : 'text-muted-foreground'
                                    }`}
                                >
                                    {r.pend_cents > 0
                                        ? formatCentsMx(r.pend_cents)
                                        : '—'}
                                </td>
                                <td className="px-4 py-2.5 text-right text-muted-foreground tabular-nums">
                                    {totals.solicitado > 0
                                        ? ((r.solicitado_cents / totals.solicitado) * 100).toFixed(1)
                                        : '0.0'}
                                    %
                                </td>
                                <td className="px-4 py-2.5">
                                    <DistributionBar
                                        value={r.solicitado_cents}
                                        max={maxSol}
                                    />
                                </td>
                            </tr>
                        ))
                    )}
                </tbody>
                {rows.length > 0 ? (
                    <tfoot className="bg-[var(--card-soft)] text-sm font-semibold">
                        <tr>
                            <td className="px-4 py-2.5">
                                Total · {rows.length} {groupLabel.toLowerCase()}
                                {rows.length === 1 ? '' : 's'}
                            </td>
                            <td className="px-4 py-2.5 text-right tabular-nums">
                                {fmtNumber(totals.count)}
                            </td>
                            <td className="px-4 py-2.5 text-right tabular-nums">
                                {formatCentsMx(totals.solicitado)}
                            </td>
                            <td className="px-4 py-2.5 text-right tabular-nums">
                                {formatCentsMx(totals.aprobado)}
                            </td>
                            <td className="px-4 py-2.5 text-right tabular-nums text-[var(--warning-fg)]">
                                {formatCentsMx(totals.pend)}
                            </td>
                            <td className="px-4 py-2.5 text-right tabular-nums">
                                100%
                            </td>
                            <td />
                        </tr>
                    </tfoot>
                ) : null}
            </table>
        </section>
    );
}
