import { Check, Circle, Clock, X } from 'lucide-react';
import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

export type ApprovalTimelineStatus =
    | 'done'
    | 'current'
    | 'pending'
    | 'rejected';

export type ApprovalTimelineStep = {
    actor: ReactNode;
    role?: ReactNode;
    status: ApprovalTimelineStatus;
    timestamp?: ReactNode;
    note?: ReactNode;
};

type ApprovalTimelineProps = {
    steps: ApprovalTimelineStep[];
    className?: string;
};

function StepIcon({ status }: { status: ApprovalTimelineStatus }) {
    if (status === 'done') {
        return <Check className="size-3.5" aria-hidden />;
    }

    if (status === 'current') {
        return <Clock className="size-3.5" aria-hidden />;
    }

    if (status === 'rejected') {
        return <X className="size-3.5" aria-hidden />;
    }

    return <Circle className="size-2.5" aria-hidden />;
}

export function ApprovalTimeline({ steps, className }: ApprovalTimelineProps) {
    return (
        <div className={cn('idh-timeline', className)}>
            {steps.map((step, idx) => (
                <div
                    key={idx}
                    className={cn(
                        'idh-timeline-step',
                        step.status === 'done' && 'is-done',
                        step.status === 'current' && 'is-current',
                        step.status === 'rejected' && 'is-rejected',
                    )}
                >
                    <div className="idh-timeline-bullet">
                        <StepIcon status={step.status} />
                    </div>
                    <div className="pt-1">
                        <div className="text-sm leading-snug font-semibold">
                            {step.actor}
                            {step.role ? (
                                <span className="ml-1 font-normal text-muted-foreground">
                                    · {step.role}
                                </span>
                            ) : null}
                        </div>
                        {step.timestamp ? (
                            <div className="mt-0.5 text-xs text-muted-foreground">
                                {step.timestamp}
                            </div>
                        ) : null}
                        {step.note ? (
                            <div className="mt-2 rounded-md border border-border bg-[var(--card-soft)] px-2.5 py-2 text-[13px] leading-snug">
                                {step.note}
                            </div>
                        ) : null}
                    </div>
                </div>
            ))}
        </div>
    );
}
