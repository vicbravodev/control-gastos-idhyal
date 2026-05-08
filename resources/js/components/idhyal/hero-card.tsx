import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

type HeroCardProps = {
    emblem: ReactNode;
    title: ReactNode;
    subtitle?: ReactNode;
    eyebrow?: ReactNode;
    actions?: ReactNode;
    className?: string;
};

export function HeroCard({
    emblem,
    title,
    subtitle,
    eyebrow,
    actions,
    className,
}: HeroCardProps) {
    return (
        <div
            className={cn(
                'relative flex flex-col gap-4 overflow-hidden rounded-[var(--radius-xl)] border border-border bg-card px-6 py-5 sm:flex-row sm:items-center sm:gap-5 sm:px-7',
                className,
            )}
        >
            <div
                aria-hidden
                className="pointer-events-none absolute -top-12 -right-16 h-48 w-48 rounded-full bg-gradient-to-br from-[var(--brand-blue-50)] to-[var(--brand-gold-50)] opacity-60"
            />
            <div className="relative grid size-[72px] shrink-0 place-items-center rounded-2xl border border-border bg-card p-2 shadow-xs">
                {emblem}
            </div>
            <div className="relative min-w-0 flex-1">
                {eyebrow ? (
                    <div className="t-eyebrow mb-1 text-[var(--brand-blue-700)]">
                        {eyebrow}
                    </div>
                ) : null}
                <h1 className="text-[1.5rem] leading-tight font-bold tracking-[-0.02em]">
                    {title}
                </h1>
                {subtitle ? (
                    <p className="mt-1 text-sm text-muted-foreground">
                        {subtitle}
                    </p>
                ) : null}
            </div>
            {actions ? (
                <div className="relative flex flex-wrap items-center gap-2">
                    {actions}
                </div>
            ) : null}
        </div>
    );
}
