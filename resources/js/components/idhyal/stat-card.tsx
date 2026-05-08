import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

type DeltaTone = 'positive' | 'negative' | 'neutral';

type StatCardProps = {
    label: ReactNode;
    value: ReactNode;
    hint?: ReactNode;
    delta?: ReactNode;
    deltaTone?: DeltaTone;
    icon?: ReactNode;
    iconTone?: 'blue' | 'gold' | 'muted';
    children?: ReactNode;
    footer?: ReactNode;
    className?: string;
};

const ICON_TONE: Record<NonNullable<StatCardProps['iconTone']>, string> = {
    blue: 'bg-[var(--brand-blue-50)] text-[var(--brand-blue-700)]',
    gold: 'bg-[var(--brand-gold-100)] text-[var(--brand-gold-700)]',
    muted: 'bg-muted text-muted-foreground',
};

const DELTA_TONE: Record<DeltaTone, string> = {
    positive: 'text-[var(--success-fg)]',
    negative: 'text-[var(--destructive-fg)]',
    neutral: 'text-muted-foreground',
};

export function StatCard({
    label,
    value,
    hint,
    delta,
    deltaTone = 'positive',
    icon,
    iconTone = 'blue',
    children,
    footer,
    className,
}: StatCardProps) {
    return (
        <div
            className={cn(
                'flex h-full flex-col rounded-xl border border-border bg-card p-5 shadow-xs',
                className,
            )}
        >
            <div className="flex items-start gap-3">
                {icon ? (
                    <div
                        className={cn(
                            'grid size-10 shrink-0 place-items-center rounded-lg',
                            ICON_TONE[iconTone],
                        )}
                    >
                        {icon}
                    </div>
                ) : null}
                <div className="min-w-0 flex-1">
                    <div className="text-xs font-medium text-muted-foreground">
                        {label}
                    </div>
                    <div className="mt-1.5 flex items-baseline gap-2">
                        <div className="t-num text-2xl font-bold tracking-[-0.02em]">
                            {value}
                        </div>
                        {delta ? (
                            <div
                                className={cn(
                                    'text-xs font-semibold',
                                    DELTA_TONE[deltaTone],
                                )}
                            >
                                {delta}
                            </div>
                        ) : null}
                    </div>
                    {hint ? (
                        <div className="mt-0.5 text-xs text-muted-foreground">
                            {hint}
                        </div>
                    ) : null}
                </div>
            </div>
            {children ? <div className="mt-3">{children}</div> : null}
            {footer ? <div className="mt-auto pt-3">{footer}</div> : null}
        </div>
    );
}
