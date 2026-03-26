import ExpenseRequestApprovalController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestApprovalController';
import ActiveApprovalCard from '@/components/active-approval-card';
import type { ApprovalRow } from '@/types';
import ExpenseReportReviewCard from './expense-report-review-card';
import ExpenseReportSubmitCard from './expense-report-submit-card';
import RecordPaymentCard from './record-payment-card';
import SettlementCard from './settlement-card';
import type { Detail, SettlementSummary } from './types';

type Props = {
    expenseRequest: Detail;
    canRecordPayment: boolean;
    canSaveExpenseReportDraft: boolean;
    canSubmitExpenseReport: boolean;
    canReviewExpenseReport: boolean;
    canRecordSettlementLiquidation: boolean;
    canCloseSettlement: boolean;
    canDownloadSettlementLiquidationReceipt: boolean;
    activeApproval: ApprovalRow | undefined;
};

export default function ActionRequiredCard({
    expenseRequest,
    canRecordPayment,
    canSaveExpenseReportDraft,
    canSubmitExpenseReport,
    canReviewExpenseReport,
    canRecordSettlementLiquidation,
    canCloseSettlement,
    canDownloadSettlementLiquidationReceipt,
    activeApproval,
}: Props) {
    const defaultReportedCents =
        expenseRequest.expense_report?.reported_amount_cents ??
        expenseRequest.approved_amount_cents ??
        expenseRequest.requested_amount_cents;

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

    if (canSaveExpenseReportDraft || canSubmitExpenseReport) {
        return (
            <Section label="Acción requerida: presentar comprobación">
                <ExpenseReportSubmitCard
                    expenseRequestId={expenseRequest.id}
                    defaultReportedCents={defaultReportedCents}
                    canSaveDraft={canSaveExpenseReportDraft}
                    canSubmit={canSubmitExpenseReport}
                />
            </Section>
        );
    }

    if (canReviewExpenseReport) {
        return (
            <Section label="Acción requerida: revisar comprobación">
                <ExpenseReportReviewCard
                    expenseRequestId={expenseRequest.id}
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
            <p className="text-xs font-semibold uppercase tracking-wider text-amber-700 dark:text-amber-400">
                {label}
            </p>
            {children}
        </div>
    );
}
