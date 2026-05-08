import { Download } from 'lucide-react';
import ExpenseRequestController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestController';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCentsMx } from '@/lib/money';
import { DataRow } from './types';
import type { ExpenseReportSummary } from './types';

export default function ExpenseReportSummaryCard({
    expenseRequestId,
    report,
    canDownloadPdf,
    canDownloadXml,
}: {
    expenseRequestId: number;
    report: ExpenseReportSummary;
    canDownloadPdf: boolean;
    canDownloadXml: boolean;
}) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Comprobación de gasto</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="divide-y">
                    <DataRow label="Estado">
                        <StatusBadge status={report.status} />
                    </DataRow>
                    <DataRow label="Tipo de documento">
                        {report.document_type_label}
                    </DataRow>
                    <DataRow label="Monto comprobado">
                        <span className="tabular-nums">
                            {formatCentsMx(report.reported_amount_cents)}
                        </span>
                    </DataRow>
                    {report.submitted_at && (
                        <DataRow label="Enviada">{report.submitted_at}</DataRow>
                    )}
                    <DataRow label="Documentos">
                        {report.document_type === 'factura'
                            ? report.has_pdf_and_xml
                                ? 'PDF y XML completos'
                                : 'Faltan PDF o XML'
                            : report.verification_pdf_attachment_id != null
                              ? 'PDF cargado'
                              : 'Falta PDF'}
                    </DataRow>
                </div>
                {report.cfdi && (
                    <div className="mt-4 rounded-md border bg-muted/30 p-3">
                        <div className="mb-2 text-sm font-semibold">
                            Datos del CFDI
                        </div>
                        <div className="divide-y">
                            <DataRow label="UUID">
                                <span className="font-mono text-xs">
                                    {report.cfdi.uuid}
                                </span>
                            </DataRow>
                            {report.cfdi.emisor_nombre && (
                                <DataRow label="Emisor">
                                    {report.cfdi.emisor_nombre}
                                    {report.cfdi.emisor_rfc && (
                                        <span className="ml-1 font-mono text-xs text-muted-foreground">
                                            ({report.cfdi.emisor_rfc})
                                        </span>
                                    )}
                                </DataRow>
                            )}
                            {report.cfdi.receptor_rfc && (
                                <DataRow label="Receptor RFC">
                                    <span className="font-mono">
                                        {report.cfdi.receptor_rfc}
                                    </span>
                                </DataRow>
                            )}
                            {report.cfdi.fecha && (
                                <DataRow label="Fecha factura">
                                    {new Date(
                                        report.cfdi.fecha,
                                    ).toLocaleDateString('es-MX')}
                                </DataRow>
                            )}
                            {(report.cfdi.serie || report.cfdi.folio) && (
                                <DataRow label="Serie / Folio">
                                    {[report.cfdi.serie, report.cfdi.folio]
                                        .filter(Boolean)
                                        .join(' / ') || '—'}
                                </DataRow>
                            )}
                            {report.cfdi.forma_pago && (
                                <DataRow label="Forma de pago">
                                    {report.cfdi.forma_pago}
                                </DataRow>
                            )}
                            {report.cfdi.metodo_pago && (
                                <DataRow label="Método de pago">
                                    {report.cfdi.metodo_pago}
                                </DataRow>
                            )}
                            {report.cfdi.uso_cfdi && (
                                <DataRow label="Uso CFDI">
                                    {report.cfdi.uso_cfdi}
                                </DataRow>
                            )}
                        </div>
                        {report.cfdi.conceptos.length > 0 && (
                            <div className="mt-3">
                                <div className="mb-1 text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                    Conceptos
                                </div>
                                <ul className="space-y-1 text-sm">
                                    {report.cfdi.conceptos.map((c, i) => (
                                        <li
                                            key={i}
                                            className="flex justify-between gap-3 border-b pb-1 last:border-b-0"
                                        >
                                            <span className="truncate">
                                                {c.descripcion ?? '—'}
                                            </span>
                                            {c.importe != null && (
                                                <span className="shrink-0 text-muted-foreground tabular-nums">
                                                    {formatCentsMx(
                                                        Math.round(
                                                            c.importe * 100,
                                                        ),
                                                    )}
                                                </span>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}
                    </div>
                )}
                <div className="mt-3 flex flex-wrap gap-2">
                    {canDownloadPdf &&
                        report.verification_pdf_attachment_id != null && (
                            <Button variant="outline" size="sm" asChild>
                                <a
                                    href={ExpenseRequestController.downloadExpenseReportVerificationAttachment.url(
                                        {
                                            expense_request: expenseRequestId,
                                            attachment:
                                                report.verification_pdf_attachment_id,
                                        },
                                    )}
                                >
                                    <Download className="mr-1.5 size-3.5" />
                                    PDF comprobación
                                </a>
                            </Button>
                        )}
                    {canDownloadXml &&
                        report.verification_xml_attachment_id != null && (
                            <Button variant="outline" size="sm" asChild>
                                <a
                                    href={ExpenseRequestController.downloadExpenseReportVerificationAttachment.url(
                                        {
                                            expense_request: expenseRequestId,
                                            attachment:
                                                report.verification_xml_attachment_id,
                                        },
                                    )}
                                >
                                    <Download className="mr-1.5 size-3.5" />
                                    XML comprobación
                                </a>
                            </Button>
                        )}
                </div>
            </CardContent>
        </Card>
    );
}
