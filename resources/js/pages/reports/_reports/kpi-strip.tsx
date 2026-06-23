import { Banknote, CheckCircle2, CircleDollarSign, Clock, Receipt } from 'lucide-react';

import { KpiCard } from '@/components/idhyal';
import { formatCentsMx } from '@/lib/money';

import type { Kpis, SparklineMap } from './types';

type Props = {
    kpis: Kpis;
    sparklines: SparklineMap;
    compare: boolean;
};

function fmtNumber(n: number): string {
    return new Intl.NumberFormat('es-MX').format(n);
}

export function KpiStrip({ kpis, sparklines, compare }: Props) {
    const approvalPct =
        kpis.total_requested_cents > 0
            ? Math.round((kpis.total_approved_cents / kpis.total_requested_cents) * 100)
            : 0;
    const paymentPct =
        kpis.total_approved_cents > 0
            ? Math.round((kpis.total_paid_cents / kpis.total_approved_cents) * 100)
            : 0;

    const approvalDelta = kpis.avg_approval_hours_delta;
    const approvalDir =
        approvalDelta == null
            ? undefined
            : approvalDelta < 0
              ? 'up'
              : approvalDelta > 0
                ? 'down'
                : 'flat';

    return (
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
            <KpiCard
                icon={Receipt}
                tone="blue"
                label="Total solicitudes"
                value={fmtNumber(kpis.total_count)}
                sparkline={sparklines.count}
                delta={
                    compare && kpis.total_count_delta_pct != null
                        ? { pct: kpis.total_count_delta_pct }
                        : undefined
                }
            />
            <KpiCard
                icon={CircleDollarSign}
                tone="blue"
                label="Monto solicitado"
                value={formatCentsMx(kpis.total_requested_cents)}
                sparkline={sparklines.solicitado}
                delta={
                    compare && kpis.total_requested_cents_delta_pct != null
                        ? { pct: kpis.total_requested_cents_delta_pct }
                        : undefined
                }
            />
            <KpiCard
                icon={CheckCircle2}
                tone="gold"
                label="Monto aprobado"
                value={formatCentsMx(kpis.total_approved_cents)}
                sparkline={sparklines.aprobado}
                sub={`${approvalPct}% del solicitado`}
                delta={
                    compare && kpis.total_approved_cents_delta_pct != null
                        ? { pct: kpis.total_approved_cents_delta_pct }
                        : undefined
                }
            />
            <KpiCard
                icon={Banknote}
                tone="gold"
                label="Monto pagado"
                value={formatCentsMx(kpis.total_paid_cents)}
                sparkline={sparklines.pagado}
                sub={`${paymentPct}% del aprobado`}
                delta={
                    compare && kpis.total_paid_cents_delta_pct != null
                        ? { pct: kpis.total_paid_cents_delta_pct }
                        : undefined
                }
            />
            <KpiCard
                icon={Clock}
                tone="warn"
                label="Tiempo prom. aprobación"
                value={
                    kpis.avg_approval_hours != null
                        ? `${kpis.avg_approval_hours.toFixed(1)} h`
                        : '—'
                }
                sub={
                    kpis.avg_approval_hours_prev != null
                        ? `Anterior: ${kpis.avg_approval_hours_prev.toFixed(1)} h`
                        : undefined
                }
                delta={
                    compare && approvalDelta != null
                        ? {
                              direction: approvalDir,
                              label:
                                  approvalDelta === 0
                                      ? 'sin cambio'
                                      : `${approvalDelta > 0 ? '+' : ''}${approvalDelta.toFixed(1)} h`,
                          }
                        : undefined
                }
            />
        </div>
    );
}
