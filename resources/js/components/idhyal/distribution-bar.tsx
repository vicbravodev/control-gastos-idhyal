import { cn } from '@/lib/utils';

type DistributionBarProps = {
    value: number;
    max: number;
    tone?: 'blue' | 'gold' | 'warn' | 'muted';
    className?: string;
};

const TONES = {
    blue: 'bg-[var(--brand-blue-500)]',
    gold: 'bg-[var(--brand-gold-500)]',
    warn: 'bg-[var(--warning)]',
    muted: 'bg-muted-foreground/40',
};

export function DistributionBar({
    value,
    max,
    tone = 'blue',
    className,
}: DistributionBarProps) {
    const pct = max > 0 ? Math.max(0, Math.min(100, (value / max) * 100)) : 0;

    return (
        <div
            className={cn(
                'h-1.5 w-full overflow-hidden rounded-full bg-muted',
                className,
            )}
            role="progressbar"
            aria-valuenow={Math.round(pct)}
            aria-valuemin={0}
            aria-valuemax={100}
        >
            <div
                className={cn('h-full rounded-full transition-[width]', TONES[tone])}
                style={{ width: `${pct}%` }}
            />
        </div>
    );
}
