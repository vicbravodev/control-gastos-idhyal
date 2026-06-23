import { ArrowDown, ArrowUp } from 'lucide-react';
import type { ComponentType, ReactNode, SVGProps } from 'react';

import { cn } from '@/lib/utils';

import { Sparkline } from './sparkline';

type Tone = 'blue' | 'gold' | 'warn';

type Delta = {
    pct?: number | null;
    direction?: 'up' | 'down' | 'flat';
    label?: string;
};

type KpiCardProps = {
    icon: ComponentType<SVGProps<SVGSVGElement>>;
    tone?: Tone;
    label: string;
    value: ReactNode;
    sub?: ReactNode;
    delta?: Delta;
    sparkline?: number[];
    className?: string;
};

const TONE_BG: Record<Tone, string> = {
    blue: 'bg-[var(--brand-blue-50)] text-[var(--brand-blue-700)]',
    gold: 'bg-[var(--brand-gold-100)] text-[var(--brand-gold-700)]',
    warn: 'bg-[var(--warning-bg)] text-[var(--warning-fg)]',
};

const TONE_SPARK: Record<Tone, 'blue' | 'gold' | 'warn'> = {
    blue: 'blue',
    gold: 'gold',
    warn: 'warn',
};

export function KpiCard({
    icon: Icon,
    tone = 'blue',
    label,
    value,
    sub,
    delta,
    sparkline,
    className,
}: KpiCardProps) {
    const dir =
        delta?.direction ??
        (typeof delta?.pct === 'number'
            ? delta.pct > 0
                ? 'up'
                : delta.pct < 0
                  ? 'down'
                  : 'flat'
            : undefined);

    const deltaTone =
        dir === 'up'
            ? 'text-[var(--success-fg)]'
            : dir === 'down'
              ? 'text-red-600 dark:text-red-400'
              : 'text-muted-foreground';

    const deltaLabel =
        delta?.label ??
        (typeof delta?.pct === 'number'
            ? `${delta.pct > 0 ? '+' : ''}${delta.pct.toFixed(1)}%`
            : undefined);

    return (
        <div
            className={cn(
                'flex flex-col gap-2 rounded-xl border border-border bg-card p-4 transition-colors',
                className,
            )}
        >
            <div className="flex items-center justify-between gap-3">
                <span
                    className={cn(
                        'inline-flex size-8 items-center justify-center rounded-lg',
                        TONE_BG[tone],
                    )}
                >
                    <Icon className="size-4" aria-hidden />
                </span>
                {sparkline && sparkline.length > 1 ? (
                    <Sparkline
                        values={sparkline}
                        tone={TONE_SPARK[tone]}
                        aria-label={`${label} tendencia`}
                    />
                ) : null}
            </div>
            <div className="text-xs font-medium text-muted-foreground">
                {label}
            </div>
            <div className="t-num text-2xl font-semibold tracking-tight tabular-nums">
                {value}
            </div>
            {(deltaLabel || sub) && (
                <div className="flex items-center gap-2 text-xs">
                    {deltaLabel ? (
                        <span
                            className={cn(
                                'inline-flex items-center gap-1 font-semibold',
                                deltaTone,
                            )}
                        >
                            {dir === 'up' && <ArrowUp className="size-3" />}
                            {dir === 'down' && <ArrowDown className="size-3" />}
                            {deltaLabel}
                        </span>
                    ) : null}
                    {sub ? (
                        <span className="text-muted-foreground">· {sub}</span>
                    ) : null}
                </div>
            )}
        </div>
    );
}
