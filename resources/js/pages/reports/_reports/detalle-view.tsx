import { Link } from '@inertiajs/react';
import { ArrowLeftRight, Banknote } from 'lucide-react';
import { useState } from 'react';

import ExpenseRequestController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestController';
import { PaginationNav } from '@/components/pagination-nav';
import { StatusBadge } from '@/components/status-badge';
import { Checkbox } from '@/components/ui/checkbox';
import { formatCentsMx } from '@/lib/money';

import type { DetailRow, Paginator } from './types';

type ColumnId =
    | 'folio'
    | 'solicitante'
    | 'region'
    | 'concepto'
    | 'estado'
    | 'entrega'
    | 'solicitado'
    | 'aprobado'
    | 'pagado'
    | 'fecha';

const ALL_COLUMNS: Array<{ id: ColumnId; label: string }> = [
    { id: 'folio', label: 'Folio' },
    { id: 'solicitante', label: 'Solicitante' },
    { id: 'region', label: 'Región / Estado' },
    { id: 'concepto', label: 'Concepto' },
    { id: 'estado', label: 'Estado' },
    { id: 'entrega', label: 'Entrega' },
    { id: 'solicitado', label: 'Solicitado' },
    { id: 'aprobado', label: 'Aprobado' },
    { id: 'pagado', label: 'Pagado' },
    { id: 'fecha', label: 'Fecha' },
];

type Props = {
    paginator: Paginator<DetailRow>;
    columnsOpen: boolean;
};

const dateFormatter = new Intl.DateTimeFormat('es-MX', {
    day: '2-digit',
    month: 'short',
    year: '2-digit',
});

function formatDate(iso: string | null): string {
    if (!iso) return '—';
    try {
        return dateFormatter.format(new Date(iso));
    } catch {
        return '—';
    }
}

export function DetalleView({ paginator, columnsOpen }: Props) {
    const [hidden, setHidden] = useState<Set<ColumnId>>(new Set());

    const visible = (id: ColumnId) => !hidden.has(id);

    const toggleColumn = (id: ColumnId) => {
        setHidden((prev) => {
            const next = new Set(prev);
            if (next.has(id)) {
                next.delete(id);
            } else {
                next.add(id);
            }
            return next;
        });
    };

    const totals = paginator.data.reduce(
        (acc, r) => ({
            solicitado: acc.solicitado + r.requested_amount_cents,
            aprobado: acc.aprobado + (r.approved_amount_cents ?? 0),
            pagado: acc.pagado + r.paid_amount_cents,
        }),
        { solicitado: 0, aprobado: 0, pagado: 0 },
    );

    return (
        <div className="flex flex-col gap-3">
            {columnsOpen && (
                <div className="rounded-xl border border-border bg-card p-3">
                    <div className="flex flex-wrap gap-3">
                        {ALL_COLUMNS.map((c) => (
                            <label
                                key={c.id}
                                className="inline-flex items-center gap-2 text-sm"
                            >
                                <Checkbox
                                    checked={!hidden.has(c.id)}
                                    onCheckedChange={() => toggleColumn(c.id)}
                                />
                                {c.label}
                            </label>
                        ))}
                    </div>
                </div>
            )}
            <div className="overflow-hidden rounded-xl border border-border bg-card">
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="border-b border-border bg-[var(--card-soft)] text-[11px] uppercase tracking-wider text-muted-foreground">
                            <tr>
                                {visible('folio') && (
                                    <th className="px-4 py-2.5 text-left font-semibold">
                                        Folio
                                    </th>
                                )}
                                {visible('solicitante') && (
                                    <th className="px-4 py-2.5 text-left font-semibold">
                                        Solicitante
                                    </th>
                                )}
                                {visible('region') && (
                                    <th className="px-4 py-2.5 text-left font-semibold">
                                        Región / Estado
                                    </th>
                                )}
                                {visible('concepto') && (
                                    <th className="px-4 py-2.5 text-left font-semibold">
                                        Concepto
                                    </th>
                                )}
                                {visible('estado') && (
                                    <th className="px-4 py-2.5 text-left font-semibold">
                                        Estado
                                    </th>
                                )}
                                {visible('entrega') && (
                                    <th className="px-4 py-2.5 text-left font-semibold">
                                        Entrega
                                    </th>
                                )}
                                {visible('solicitado') && (
                                    <th className="px-4 py-2.5 text-right font-semibold">
                                        Solicitado
                                    </th>
                                )}
                                {visible('aprobado') && (
                                    <th className="px-4 py-2.5 text-right font-semibold">
                                        Aprobado
                                    </th>
                                )}
                                {visible('pagado') && (
                                    <th className="px-4 py-2.5 text-right font-semibold">
                                        Pagado
                                    </th>
                                )}
                                {visible('fecha') && (
                                    <th className="px-4 py-2.5 text-left font-semibold">
                                        Fecha
                                    </th>
                                )}
                            </tr>
                        </thead>
                        <tbody>
                            {paginator.data.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={10}
                                        className="px-4 py-10 text-center text-sm text-muted-foreground"
                                    >
                                        Sin resultados con los filtros actuales.
                                    </td>
                                </tr>
                            ) : (
                                paginator.data.map((row) => (
                                    <tr
                                        key={row.id}
                                        className="border-t border-border transition-colors hover:bg-[var(--card-soft)]"
                                    >
                                        {visible('folio') && (
                                            <td className="px-4 py-2.5">
                                                <Link
                                                    href={ExpenseRequestController.show.url(
                                                        row.id,
                                                    )}
                                                    className="t-folio text-[var(--brand-blue-700)] hover:underline"
                                                >
                                                    {row.folio ?? `#${row.id}`}
                                                </Link>
                                            </td>
                                        )}
                                        {visible('solicitante') && (
                                            <td className="px-4 py-2.5">
                                                <div className="font-medium">
                                                    {row.user_name}
                                                </div>
                                                <div className="text-xs text-muted-foreground">
                                                    {row.user_role ?? '—'}
                                                </div>
                                            </td>
                                        )}
                                        {visible('region') && (
                                            <td className="px-4 py-2.5 text-xs">
                                                <div>{row.region_name ?? '—'}</div>
                                                <div className="text-muted-foreground">
                                                    {row.state_name ?? ''}
                                                </div>
                                            </td>
                                        )}
                                        {visible('concepto') && (
                                            <td className="max-w-[220px] px-4 py-2.5">
                                                <div className="truncate">
                                                    {row.concept_label}
                                                </div>
                                            </td>
                                        )}
                                        {visible('estado') && (
                                            <td className="px-4 py-2.5">
                                                <StatusBadge status={row.status} />
                                            </td>
                                        )}
                                        {visible('entrega') && (
                                            <td className="px-4 py-2.5 text-xs">
                                                <span className="inline-flex items-center gap-1.5 text-muted-foreground">
                                                    {row.delivery_method === 'cash' ? (
                                                        <Banknote className="size-3.5" />
                                                    ) : (
                                                        <ArrowLeftRight className="size-3.5" />
                                                    )}
                                                    {row.delivery_method === 'cash'
                                                        ? 'Efectivo'
                                                        : 'Transferencia'}
                                                </span>
                                            </td>
                                        )}
                                        {visible('solicitado') && (
                                            <td className="px-4 py-2.5 text-right font-semibold tabular-nums">
                                                {formatCentsMx(row.requested_amount_cents)}
                                            </td>
                                        )}
                                        {visible('aprobado') && (
                                            <td className="px-4 py-2.5 text-right tabular-nums">
                                                {row.approved_amount_cents != null
                                                    ? formatCentsMx(row.approved_amount_cents)
                                                    : '—'}
                                            </td>
                                        )}
                                        {visible('pagado') && (
                                            <td className="px-4 py-2.5 text-right tabular-nums">
                                                {row.paid_amount_cents > 0
                                                    ? formatCentsMx(row.paid_amount_cents)
                                                    : '—'}
                                            </td>
                                        )}
                                        {visible('fecha') && (
                                            <td className="px-4 py-2.5 whitespace-nowrap text-xs text-muted-foreground">
                                                {formatDate(row.created_at)}
                                            </td>
                                        )}
                                    </tr>
                                ))
                            )}
                        </tbody>
                        {paginator.data.length > 0 ? (
                            <tfoot className="bg-[var(--card-soft)] text-sm font-semibold">
                                <tr>
                                    {visible('folio') && <td />}
                                    {(visible('solicitante') ||
                                        visible('region') ||
                                        visible('concepto') ||
                                        visible('estado') ||
                                        visible('entrega')) && (
                                        <td
                                            colSpan={
                                                (visible('solicitante') ? 1 : 0) +
                                                (visible('region') ? 1 : 0) +
                                                (visible('concepto') ? 1 : 0) +
                                                (visible('estado') ? 1 : 0) +
                                                (visible('entrega') ? 1 : 0)
                                            }
                                            className="px-4 py-2.5"
                                        >
                                            Subtotal página · {paginator.data.length} de{' '}
                                            {paginator.total}
                                        </td>
                                    )}
                                    {visible('solicitado') && (
                                        <td className="px-4 py-2.5 text-right tabular-nums">
                                            {formatCentsMx(totals.solicitado)}
                                        </td>
                                    )}
                                    {visible('aprobado') && (
                                        <td className="px-4 py-2.5 text-right tabular-nums">
                                            {formatCentsMx(totals.aprobado)}
                                        </td>
                                    )}
                                    {visible('pagado') && (
                                        <td className="px-4 py-2.5 text-right tabular-nums">
                                            {formatCentsMx(totals.pagado)}
                                        </td>
                                    )}
                                    {visible('fecha') && <td />}
                                </tr>
                            </tfoot>
                        ) : null}
                    </table>
                </div>
            </div>
            {paginator.last_page > 1 && (
                <PaginationNav
                    links={paginator.links}
                    currentPage={paginator.current_page}
                    lastPage={paginator.last_page}
                />
            )}
        </div>
    );
}
