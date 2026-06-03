import ExpenseRequestApprovalController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestApprovalController';
import ActiveApprovalCard from '@/components/active-approval-card';
import type { ApprovalRow } from '@/types';
import ExpenseReportSubmitCard from './expense-report-submit-card';
import RecordPaymentCard from './record-payment-card';
import SettlementCard from './settlement-card';
import type { Detail, SettlementSummary } from './types';

type Props = {
    expenseRequest: Detail;
    canRecordPayment: boolean;
    canSubmitExpenseReport: boolean;
    canRecordSettlementLiquidation: boolean;
    canCloseSettlement: boolean;
    canDownloadSettlementLiquidationReceipt: boolean;
    activeApproval: ApprovalRow | undefined;
};

export default function ActionRequiredCard({
    expenseRequest,
    canRecordPayment,
    canSubmitExpenseReport,
    canRecordSettlementLiquidation,
    canCloseSettlement,
    canDownloadSettlementLiquidationReceipt,
    activeApproval,
}: Props) {
    const remainingCents = expenseRequest.balance?.remaining_cents ?? null;
    const defaultReportedCents =
        remainingCents != null && remainingCents > 0
            ? remainingCents
            : (expenseRequest.approved_amount_cents ??
              expenseRequest.requested_amount_cents);

    if (activeApproval) {
        return (
            <Section label="Tu aprobación es requerida">
                <ActiveApprovalCard
                    approval={activeApproval}
                    approveAction={ExpenseRequestApprovalController.approve.form.post(
                        {
                            expenseRequest: expenseRequest.id,
                            approval: activeApproval.id,
                        },
                    )}
                    rejectAction={ExpenseRequestApprovalController.reject.form.post(
                        {
                            expenseRequest: expenseRequest.id,
                            approval: activeApproval.id,
                        },
                    )}
                />
            </Section>
        );
    }

    if (canRecordPayment) {
        return (
            <Section label="Acción requerida: registrar pago">
                <RecordPaymentCard
                    expenseRequestId={expenseRequest.id}
                    defaultAmountCents={
                        expenseRequest.approved_amount_cents ?? 0
                    }
                />
            </Section>
        );
    }

    if (canSubmitExpenseReport) {
        return (
            <Section label="Acción requerida: presentar comprobación">
                <ExpenseReportSubmitCard
                    expenseRequestId={expenseRequest.id}
                    defaultReportedCents={defaultReportedCents}
                    canSubmit={canSubmitExpenseReport}
                />
            </Section>
        );
    }

    if (
        expenseRequest.settlement &&
        (canRecordSettlementLiquidation || canCloseSettlement)
    ) {
        return (
            <Section label="Acción requerida: liquidar balance">
                <SettlementCard
                    expenseRequestId={expenseRequest.id}
                    settlement={expenseRequest.settlement as SettlementSummary}
                    canRecordLiquidation={canRecordSettlementLiquidation}
                    canClose={canCloseSettlement}
                    canDownloadLiquidationReceipt={
                        canDownloadSettlementLiquidationReceipt
                    }
                />
            </Section>
        );
    }

    return null;
}

function Section({
    label,
    children,
}: {
    label: string;
    children: React.ReactNode;
}) {
    return (
        <div className="space-y-3">
            <p className="text-xs font-semibold tracking-wider text-amber-700 uppercase dark:text-amber-400">
                {label}
            </p>
            {children}
        </div>
    );
}
