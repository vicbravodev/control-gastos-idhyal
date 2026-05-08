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
