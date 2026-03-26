import { Download, FileText } from 'lucide-react';
import ExpenseRequestController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestController';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatCentsMx } from '@/lib/money';
import { DataRow, type PaymentSummary } from './types';

export default function PaymentSummaryCard({
    expenseRequestId,
    payment,
    canDownloadEvidence,
}: {
    expenseRequestId: number;
    payment: PaymentSummary;
    canDownloadEvidence: boolean;
}) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Pago registrado</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="divide-y">
                    <DataRow label="Monto">
                        <span className="tabular-nums">
                            {formatCentsMx(payment.amount_cents)}
                        </span>
                    </DataRow>
                    <DataRow label="Método">
                        {payment.payment_method === 'transfer'
                            ? 'Transferencia'
                            : 'Efectivo'}
                    </DataRow>
                    <DataRow label="Fecha">{payment.paid_on}</DataRow>
                    {payment.transfer_reference && (
                        <DataRow label="Referencia">
                            {payment.transfer_reference}
                        </DataRow>
                    )}
                    <DataRow label="Registrado por">
                        {payment.recorded_by}
                    </DataRow>
                </div>
                {payment.evidence_original_filename && (
                    <div className="mt-3 flex items-center gap-2 rounded-md bg-muted/50 px-3 py-2 text-sm">
                        <FileText className="size-4 text-muted-foreground" />
                        <span>{payment.evidence_original_filename}</span>
                        {canDownloadEvidence &&
                            payment.evidence_attachment_id != null && (
                                <Button
                                    variant="link"
                                    className="ml-auto h-auto p-0 text-sm"
                                    asChild
                                >
                                    <a
                                        href={ExpenseRequestController.downloadPaymentEvidence.url(
                                            {
                                                expense_request:
                                                    expenseRequestId,
                                                attachment:
                                                    payment.evidence_attachment_id,
                                            },
                                        )}
                                    >
                                        <Download className="mr-1 size-3.5" />
                                        Descargar
                                    </a>
                                </Button>
                            )}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
