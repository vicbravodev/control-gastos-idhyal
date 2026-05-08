import { formatCentsMx } from '@/lib/money';
import { cn } from '@/lib/utils';

type BudgetGaugeProps = {
    /** Total assigned amount in cents. */
    assignedCents: number;
    /** Committed (approved but not yet paid) amount in cents. */
    committedCents?: number;
    /** Spent (paid) amount in cents. */
    spentCents?: number;
    /** Hide the segment legend below the bar. */
    compact?: boolean;
    className?: string;
};

function clampPct(n: number): number {
    if (Number.isNaN(n)) {
        return 0;
    }

    if (n < 0) {
        return 0;
    }

    if (n > 100) {
        return 100;
    }

    return n;
}

export function BudgetGauge({
    assignedCents,
    committedCents = 0,
    spentCents = 0,
    compact,
    className,
}: BudgetGaugeProps) {
    const safeAssigned = Math.max(assignedCents, 1);
    const used = committedCents + spentCents;
    const available = Math.max(assignedCents - used, 0);
    const pctSpend = clampPct((spentCents / safeAssigned) * 100);
    const pctCommit = clampPct((committedCents / safeAssigned) * 100);
    const pctAvailable = clampPct((available / safeAssigned) * 100);

    let availableTone = 'text-[var(--success-fg)]';

    if (pctAvailable < 30) {
        availableTone = 'text-[var(--warning-fg)]';
    }

    if (pctAvailable < 10) {
        availableTone = 'text-[var(--destructive-fg)]';
    }

    return (
        <div className={cn('w-full', className)}>
            <div
                className="idh-progress"
                role="progressbar"
                aria-valuenow={Math.round(pctSpend + pctCommit)}
                aria-valuemin={0}
                aria-valuemax={100}
                aria-label={`Disponible ${formatCentsMx(available)} de ${formatCentsMx(assignedCents)}`}
            >
                <span className="seg-spend" style={{ width: `${pctSpend}%` }} />
                <span
                    className="seg-commit"
                    style={{ width: `${pctCommit}%` }}
                />
            </div>
            {!compact ? (
                <div className="mt-2 flex flex-wrap justify-between gap-x-4 gap-y-1 text-xs text-muted-foreground">
                    <span className="inline-flex items-center gap-1.5">
                        <span className="size-2 rounded-sm bg-[var(--brand-blue-500)]" />
                        Gastado {formatCentsMx(spentCents)}
                    </span>
                    <span className="inline-flex items-center gap-1.5">
                        <span className="size-2 rounded-sm bg-[var(--brand-gold-500)]" />
                        Comprom. {formatCentsMx(committedCents)}
                    </span>
                    <span className={cn('font-semibold', availableTone)}>
                        Disp. {formatCentsMx(available)}
                    </span>
                </div>
            ) : null}
        </div>
    );
}
