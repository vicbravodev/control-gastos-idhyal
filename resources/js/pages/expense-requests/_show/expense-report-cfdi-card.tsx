import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCentsMx } from '@/lib/money';
import type { CfdiSummary } from './types';
import { DataRow } from './types';

export default function ExpenseReportCfdiCard({
    cfdi,
    label,
}: {
    cfdi: CfdiSummary;
    label?: string | null;
}) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>
                    {label
                        ? `Comprobante — ${label}`
                        : 'Datos del CFDI'}
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div className="divide-y">
                    <DataRow label="UUID">
                        <span className="font-mono text-xs">{cfdi.uuid}</span>
                    </DataRow>
                    {cfdi.emisor_nombre && (
                        <DataRow label="Emisor">
                            <span>
                                {cfdi.emisor_nombre}
                                {cfdi.emisor_rfc && (
                                    <span className="ml-1 font-mono text-xs text-muted-foreground">
                                        ({cfdi.emisor_rfc})
                                    </span>
                                )}
                            </span>
                        </DataRow>
                    )}
                    {cfdi.receptor_rfc && (
                        <DataRow label="Receptor">
                            <span>
                                {cfdi.receptor_nombre ?? '—'}
                                <span className="ml-1 font-mono text-xs text-muted-foreground">
                                    ({cfdi.receptor_rfc})
                                </span>
                            </span>
                        </DataRow>
                    )}
                    {cfdi.fecha && (
                        <DataRow label="Fecha factura">
                            {new Date(cfdi.fecha).toLocaleDateString('es-MX')}
                        </DataRow>
                    )}
                    {(cfdi.serie || cfdi.folio) && (
                        <DataRow label="Serie / Folio">
                            {[cfdi.serie, cfdi.folio]
                                .filter(Boolean)
                                .join(' / ') || '—'}
                        </DataRow>
                    )}
                    {cfdi.forma_pago && (
                        <DataRow label="Forma de pago">
                            {cfdi.forma_pago}
                        </DataRow>
                    )}
                    {cfdi.metodo_pago && (
                        <DataRow label="Método de pago">
                            {cfdi.metodo_pago}
                        </DataRow>
                    )}
                    {cfdi.uso_cfdi && (
                        <DataRow label="Uso CFDI">{cfdi.uso_cfdi}</DataRow>
                    )}
                </div>
                {cfdi.conceptos.length > 0 && (
                    <div className="mt-4">
                        <p className="mb-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                            Conceptos
                        </p>
                        <ul className="divide-y rounded-md border">
                            {cfdi.conceptos.map((c, i) => (
                                <li
                                    key={i}
                                    className="flex items-baseline justify-between gap-3 px-3 py-2 text-sm"
                                >
                                    <span className="truncate">
                                        {c.descripcion ?? '—'}
                                        {c.cantidad != null && (
                                            <span className="ml-2 text-xs text-muted-foreground">
                                                ×{c.cantidad}
                                                {c.unidad
                                                    ? ` ${c.unidad}`
                                                    : ''}
                                            </span>
                                        )}
                                    </span>
                                    {c.importe != null && (
                                        <span className="shrink-0 tabular-nums text-muted-foreground">
                                            {formatCentsMx(
                                                Math.round(c.importe * 100),
                                            )}
                                        </span>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </div>
                )}
                {(cfdi.has_hidrocarburos_complement ||
                    cfdi.emisor_regimen_fiscal === '626') && (
                    <div className="mt-4 flex flex-wrap gap-2">
                        {cfdi.has_hidrocarburos_complement && (
                            <span className="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-900">
                                Combustible (IEPS)
                            </span>
                        )}
                        {cfdi.emisor_regimen_fiscal === '626' && (
                            <span className="rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-medium text-sky-900">
                                RESICO (626)
                            </span>
                        )}
                    </div>
                )}
                {cfdi.traslados.length > 0 && (
                    <div className="mt-4">
                        <p className="mb-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                            Impuestos trasladados
                        </p>
                        <ul className="divide-y rounded-md border">
                            {cfdi.traslados
                                .filter((t) => t.nivel === 'document')
                                .map((t, i) => (
                                    <li
                                        key={`tras-${i}`}
                                        className="flex items-baseline justify-between gap-3 px-3 py-2 text-sm"
                                    >
                                        <span>
                                            {t.impuesto_label}
                                            <span className="ml-2 text-xs text-muted-foreground">
                                                {t.tipo_factor}
                                                {t.tasa_o_cuota != null && (
                                                    <>
                                                        {' · '}
                                                        {t.tipo_factor === 'Cuota'
                                                            ? t.tasa_o_cuota.toFixed(4)
                                                            : `${(t.tasa_o_cuota * 100).toFixed(2)}%`}
                                                    </>
                                                )}
                                            </span>
                                        </span>
                                        <span className="shrink-0 tabular-nums text-muted-foreground">
                                            {formatCentsMx(t.importe_cents)}
                                        </span>
                                    </li>
                                ))}
                        </ul>
                    </div>
                )}
                {cfdi.retenciones.length > 0 && (
                    <div className="mt-4">
                        <p className="mb-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                            Retenciones
                        </p>
                        <ul className="divide-y rounded-md border">
                            {cfdi.retenciones
                                .filter((r) => r.nivel === 'document')
                                .map((r, i) => (
                                    <li
                                        key={`ret-${i}`}
                                        className="flex items-baseline justify-between gap-3 px-3 py-2 text-sm"
                                    >
                                        <span>
                                            {r.impuesto_label}
                                            {r.tasa_o_cuota != null && (
                                                <span className="ml-2 text-xs text-muted-foreground">
                                                    {(r.tasa_o_cuota * 100).toFixed(4)}%
                                                </span>
                                            )}
                                        </span>
                                        <span className="shrink-0 tabular-nums text-muted-foreground">
                                            −{formatCentsMx(r.importe_cents)}
                                        </span>
                                    </li>
                                ))}
                        </ul>
                    </div>
                )}
                {cfdi.impuestos_locales.length > 0 && (
                    <div className="mt-4">
                        <p className="mb-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                            Impuestos locales
                        </p>
                        <ul className="divide-y rounded-md border">
                            {cfdi.impuestos_locales.map((l, i) => (
                                <li
                                    key={`loc-${i}`}
                                    className="flex items-baseline justify-between gap-3 px-3 py-2 text-sm"
                                >
                                    <span>
                                        {l.clave}
                                        <span className="ml-2 text-xs text-muted-foreground">
                                            {l.tipo === 'retencion'
                                                ? 'Retenido'
                                                : 'Trasladado'}
                                            {l.tasa != null &&
                                                ` · ${(l.tasa * 100).toFixed(2)}%`}
                                        </span>
                                    </span>
                                    <span className="shrink-0 tabular-nums text-muted-foreground">
                                        {l.tipo === 'retencion' ? '−' : ''}
                                        {formatCentsMx(l.importe_cents)}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
