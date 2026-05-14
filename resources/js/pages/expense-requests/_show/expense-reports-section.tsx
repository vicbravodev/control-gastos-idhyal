import { AlertCircle, ChevronRight, FileText } from 'lucide-react';
import { useEffect, useState } from 'react';

import { StatusBadge } from '@/components/status-badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { formatCentsMx } from '@/lib/money';

import ExpenseReportCfdiCard from './expense-report-cfdi-card';
import ExpenseReportReviewCard from './expense-report-review-card';
import ExpenseReportSummaryCard from './expense-report-summary-card';
import type { ExpenseReportSummary } from './types';

const REVIEW_STATUS = 'accounting_review';

function reportAnchorId(reportId: number): string {
    return `report-${reportId}`;
}

function ReportBody({
    report,
    expenseRequestId,
    canDownloadPdf,
    canDownloadXml,
    canReview,
}: {
    report: ExpenseReportSummary;
    expenseRequestId: number;
    canDownloadPdf: boolean;
    canDownloadXml: boolean;
    canReview: boolean;
}) {
    const needsReview = report.status === REVIEW_STATUS;

    return (
        <div className="flex flex-col gap-4">
            {needsReview && canReview && (
                <ExpenseReportReviewCard
                    expenseRequestId={expenseRequestId}
                    expenseReportId={report.id}
                    reportLabel={report.label}
                    reportedAmountCents={report.reported_amount_cents}
                />
            )}
            <div className="grid gap-4 lg:grid-cols-2">
                <ExpenseReportSummaryCard
                    expenseRequestId={expenseRequestId}
                    report={report}
                    canDownloadPdf={canDownloadPdf}
                    canDownloadXml={canDownloadXml}
                />
                {report.cfdi ? (
                    <ExpenseReportCfdiCard
                        cfdi={report.cfdi}
                        label={report.label}
                    />
                ) : null}
            </div>
        </div>
    );
}

function CollapsibleReport({
    report,
    expenseRequestId,
    canDownloadPdf,
    canDownloadXml,
    canReview,
    index,
    open,
    onOpenChange,
}: {
    report: ExpenseReportSummary;
    expenseRequestId: number;
    canDownloadPdf: boolean;
    canDownloadXml: boolean;
    canReview: boolean;
    index: number;
    open: boolean;
    onOpenChange: (next: boolean) => void;
}) {
    const headerTitle = report.label ?? `Comprobación ${index + 1}`;
    const needsReview = report.status === REVIEW_STATUS;
    const reviewerLine = report.reviewer_name
        ? `Revisada por ${report.reviewer_name}`
        : null;

    return (
        <Collapsible
            id={reportAnchorId(report.id)}
            open={open}
            onOpenChange={onOpenChange}
            className={`scroll-mt-24 overflow-hidden rounded-xl border bg-card ${
                needsReview && canReview
                    ? 'border-amber-300 ring-1 ring-amber-200 dark:border-amber-700 dark:ring-amber-900'
                    : 'border-border'
            }`}
        >
            <CollapsibleTrigger className="group flex w-full items-center gap-3 px-5 py-3.5 text-left transition-colors hover:bg-[var(--card-soft)]">
                <ChevronRight
                    className={`size-4 shrink-0 text-muted-foreground transition-transform ${open ? 'rotate-90' : ''}`}
                    aria-hidden
                />
                <FileText
                    className="size-4 shrink-0 text-[var(--brand-blue-600)]"
                    aria-hidden
                />
                <div className="flex min-w-0 flex-col">
                    <span className="truncate text-sm font-semibold">
                        {headerTitle}
                    </span>
                    {reviewerLine ? (
                        <span className="truncate text-xs text-muted-foreground">
                            {reviewerLine}
                        </span>
                    ) : null}
                </div>
                <StatusBadge status={report.status} />
                {needsReview && canReview ? (
                    <span className="rounded-full bg-amber-200 px-2 py-0.5 text-[11px] font-semibold text-amber-900 dark:bg-amber-800 dark:text-amber-100">
                        Te toca decidir
                    </span>
                ) : null}
                <span className="t-num ml-auto text-sm font-semibold tabular-nums">
                    {formatCentsMx(report.reported_amount_cents)}
                </span>
            </CollapsibleTrigger>
            <CollapsibleContent className="border-t border-border bg-[var(--card-soft)] p-4">
                <ReportBody
                    report={report}
                    expenseRequestId={expenseRequestId}
                    canDownloadPdf={canDownloadPdf}
                    canDownloadXml={canDownloadXml}
                    canReview={canReview}
                />
            </CollapsibleContent>
        </Collapsible>
    );
}

function readReportHash(): number | null {
    if (typeof window === 'undefined') {
        return null;
    }

    const match = window.location.hash.match(/^#report-(\d+)$/);
    return match ? Number(match[1]) : null;
}

function computeInitialOpen(
    reports: ExpenseReportSummary[],
    canReview: boolean,
): Set<number> {
    const hashId = readReportHash();
    if (hashId != null && reports.some((r) => r.id === hashId)) {
        return new Set([hashId]);
    }

    if (canReview) {
        const firstInReview = reports.find(
            (r) => r.status === REVIEW_STATUS,
        );
        if (firstInReview) {
            return new Set([firstInReview.id]);
        }
    }

    if (reports.length === 1) {
        return new Set([reports[0].id]);
    }

    return new Set([reports[0].id]);
}

export default function ExpenseReportsSection({
    reports,
    expenseRequestId,
    canDownloadPdf,
    canDownloadXml,
    canReview,
}: {
    reports: ExpenseReportSummary[];
    expenseRequestId: number;
    canDownloadPdf: boolean;
    canDownloadXml: boolean;
    canReview: boolean;
}) {
    const [openIds, setOpenIds] = useState<Set<number>>(() =>
        computeInitialOpen(reports, canReview),
    );

    useEffect(() => {
        const hashId = readReportHash();
        if (hashId == null) {
            return;
        }

        const exists = reports.some((r) => r.id === hashId);
        if (!exists) {
            return;
        }

        setOpenIds((prev) => {
            if (prev.has(hashId)) return prev;
            const next = new Set(prev);
            next.add(hashId);
            return next;
        });

        const element = document.getElementById(reportAnchorId(hashId));
        element?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, [reports]);

    if (reports.length === 0) {
        return null;
    }

    const pendingForMe = canReview
        ? reports.filter((r) => r.status === REVIEW_STATUS)
        : [];

    const toggleOpen = (reportId: number, next: boolean) => {
        setOpenIds((prev) => {
            const out = new Set(prev);
            if (next) {
                out.add(reportId);
            } else {
                out.delete(reportId);
            }
            return out;
        });
    };

    const headerTitle =
        reports.length === 1
            ? 'Comprobación'
            : `Comprobaciones (${reports.length})`;

    return (
        <section className="flex flex-col gap-3">
            <header className="flex items-center gap-2.5">
                <FileText
                    className="size-4 text-[var(--brand-blue-600)]"
                    aria-hidden
                />
                <h2 className="text-sm font-semibold">{headerTitle}</h2>
            </header>

            {pendingForMe.length > 0 && (
                <Alert className="border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-200">
                    <AlertCircle className="size-4" />
                    <AlertTitle>
                        {pendingForMe.length === 1
                            ? 'Tienes 1 comprobación por revisar'
                            : `Tienes ${pendingForMe.length} comprobaciones por revisar`}
                    </AlertTitle>
                    <AlertDescription>
                        {pendingForMe.length === 1 ? (
                            <>
                                Está marcada como{' '}
                                <span className="font-semibold">
                                    “Te toca decidir”
                                </span>{' '}
                                abajo. Ábrela para aprobarla o rechazarla.
                            </>
                        ) : (
                            <>
                                <span>
                                    Están marcadas como{' '}
                                    <span className="font-semibold">
                                        “Te toca decidir”
                                    </span>
                                    :
                                </span>
                                <ul className="mt-1.5 flex flex-col gap-1">
                                    {pendingForMe.map((report, idx) => (
                                        <li key={report.id}>
                                            <a
                                                href={`#${reportAnchorId(report.id)}`}
                                                className="font-medium underline-offset-2 hover:underline"
                                                onClick={() =>
                                                    toggleOpen(report.id, true)
                                                }
                                            >
                                                {report.label ??
                                                    `Comprobación ${reports.indexOf(report) + 1}`}
                                            </a>{' '}
                                            <span className="text-xs text-amber-800/80 dark:text-amber-300/80">
                                                ·{' '}
                                                {formatCentsMx(
                                                    report.reported_amount_cents,
                                                )}
                                            </span>
                                            {idx < pendingForMe.length - 1
                                                ? ''
                                                : ''}
                                        </li>
                                    ))}
                                </ul>
                            </>
                        )}
                    </AlertDescription>
                </Alert>
            )}

            <div className="flex flex-col gap-2">
                {reports.map((report, index) => (
                    <CollapsibleReport
                        key={report.id}
                        report={report}
                        expenseRequestId={expenseRequestId}
                        canDownloadPdf={canDownloadPdf}
                        canDownloadXml={canDownloadXml}
                        canReview={canReview}
                        index={index}
                        open={openIds.has(report.id)}
                        onOpenChange={(next) => toggleOpen(report.id, next)}
                    />
                ))}
            </div>
        </section>
    );
}
