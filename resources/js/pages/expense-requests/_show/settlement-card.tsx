import { useForm, usePage } from '@inertiajs/react';
import { FileText } from 'lucide-react';
import { useState } from 'react';
import ExpenseRequestController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestController';
import ExpenseRequestSettlementController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestSettlementController';
import ConfirmationDialog from '@/components/confirmation-dialog';
import InputError from '@/components/input-error';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatCentsMx } from '@/lib/money';
import {
    DataRow,
    type SettlementLiquidationFormState,
    type SettlementSummary,
    settlementStatusLabel,
} from './types';

export default function SettlementCard({
    expenseRequestId,
    settlement,
    canRecordLiquidation,
    canClose,
    canDownloadLiquidationReceipt,
}: {
    expenseRequestId: number;
    settlement: SettlementSummary;
    canRecordLiquidation: boolean;
    canClose: boolean;
    canDownloadLiquidationReceipt: boolean;
}) {
    const { errors: sharedErrors } = usePage<{
        errors?: Record<string, string>;
    }>().props;

    const liquidationForm = useForm<SettlementLiquidationFormState>({
        evidence: null,
    });
    const closeForm = useForm<{ note: string }>({ note: '' });

    const [confirmClose, setConfirmClose] = useState(false);

    return (
        <Card>
            <CardHeader>
                <CardTitle>Balance</CardTitle>
                <CardDescription>
                    {settlementStatusLabel(settlement.status)}
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                <div className="divide-y">
                    <DataRow label="Estado">
                        <StatusBadge status={settlement.status} />
                    </DataRow>
                    <DataRow label="Base pagada">
                        <span className="tabular-nums">
                            {formatCentsMx(settlement.basis_amount_cents)}
                        </span>
                    </DataRow>
                    <DataRow label="Comprobado">
                        <span className="tabular-nums">
                            {formatCentsMx(settlement.reported_amount_cents)}
                        </span>
                    </DataRow>
                    <DataRow label="Diferencia">
                        <span className="tabular-nums font-bold">
                            {formatCentsMx(settlement.difference_cents)}
                        </span>
                    </DataRow>
                </div>
                {settlement.liquidation_evidence_original_filename && (
                    <div className="flex items-center gap-2 rounded-md bg-muted/50 px-3 py-2 text-sm">
                        <FileText className="size-4 text-muted-foreground" />
                        <span>
                            {
                                settlement.liquidation_evidence_original_filename
                            }
                        </span>
                        {canDownloadLiquidationReceipt &&
                            settlement.liquidation_evidence_attachment_id !=
                                null && (
                                <Button
                                    variant="link"
                                    className="ml-auto h-auto p-0 text-sm"
                                    asChild
                                >
                                    <a
                                        href={ExpenseRequestController.downloadSettlementLiquidationEvidence.url(
                                            {
                                                expense_request:
                                                    expenseRequestId,
                                                attachment:
                                                    settlement.liquidation_evidence_attachment_id,
                                            },
                                        )}
                                    >
                                        Descargar
                                    </a>
                                </Button>
                            )}
                    </div>
                )}
                <InputError message={sharedErrors?.settlement} />
                {canRecordLiquidation && (
                    <form
                        className="flex flex-col gap-3 border-t pt-4"
                        onSubmit={(e) => {
                            e.preventDefault();
                            liquidationForm.post(
                                ExpenseRequestSettlementController.storeLiquidation.url(
                                    { expenseRequest: expenseRequestId },
                                ),
                                {
                                    forceFormData: true,
                                    preserveScroll: true,
                                },
                            );
                        }}
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="settlement_evidence">
                                Evidencia de liquidación
                            </Label>
                            <Input
                                id="settlement_evidence"
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png,image/*,application/pdf"
                                className="cursor-pointer"
                                onChange={(ev) =>
                                    liquidationForm.setData(
                                        'evidence',
                                        ev.target.files?.[0] ?? null,
                                    )
                                }
                            />
                            <InputError
                                message={liquidationForm.errors.evidence}
                            />
                        </div>
                        <Button
                            type="submit"
                            disabled={liquidationForm.processing}
                        >
                            {liquidationForm.processing
                                ? 'Procesando…'
                                : 'Registrar liquidación'}
                        </Button>
                    </form>
                )}
                {canClose && (
                    <form
                        className="flex flex-col gap-3 border-t pt-4"
                        onSubmit={(e) => {
                            e.preventDefault();
                            setConfirmClose(true);
                        }}
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="settlement_close_note">
                                Nota de cierre (opcional)
                            </Label>
                            <Textarea
                                id="settlement_close_note"
                                rows={2}
                                value={closeForm.data.note}
                                onChange={(ev) =>
                                    closeForm.setData('note', ev.target.value)
                                }
                            />
                            <InputError message={closeForm.errors.note} />
                        </div>
                        <Button
                            type="submit"
                            variant="secondary"
                            disabled={closeForm.processing}
                        >
                            {closeForm.processing
                                ? 'Procesando…'
                                : 'Cerrar balance'}
                        </Button>
                    </form>
                )}
            </CardContent>
            <ConfirmationDialog
                open={confirmClose}
                onOpenChange={setConfirmClose}
                title="¿Cerrar este balance?"
                description="Una vez cerrado, no se podrán registrar más liquidaciones."
                confirmLabel="Cerrar balance"
                processing={closeForm.processing}
                onConfirm={() => {
                    setConfirmClose(false);
                    closeForm.post(
                        ExpenseRequestSettlementController.close.url({
                            expenseRequest: expenseRequestId,
                        }),
                        { preserveScroll: true },
                    );
                }}
            />
        </Card>
    );
}
