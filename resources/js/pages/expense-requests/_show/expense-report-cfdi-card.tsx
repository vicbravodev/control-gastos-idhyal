import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCentsMx } from '@/lib/money';
import type { CfdiSummary } from './types';
import { DataRow } from './types';

export default function ExpenseReportCfdiCard({
    cfdi,
}: {
    cfdi: CfdiSummary;
}) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Datos del CFDI</CardTitle>
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
            </CardContent>
        </Card>
    );
}
