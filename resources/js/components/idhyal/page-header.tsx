import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

type PageHeaderProps = {
    title: ReactNode;
    subtitle?: ReactNode;
    eyebrow?: ReactNode;
    actions?: ReactNode;
    className?: string;
};

export function PageHeader({
    title,
    subtitle,
    eyebrow,
    actions,
    className,
}: PageHeaderProps) {
    return (
        <div
            className={cn(
                'flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between sm:gap-5',
                className,
            )}
        >
            <div className="min-w-0">
                {eyebrow ? (
                    <div className="t-eyebrow mb-1.5 text-[var(--brand-blue-700)]">
                        {eyebrow}
                    </div>
                ) : null}
                <h1 className="text-[1.625rem] leading-tight font-bold tracking-[-0.02em]">
                    {title}
                </h1>
                {subtitle ? (
                    <p className="mt-1 text-sm text-muted-foreground">
                        {subtitle}
                    </p>
                ) : null}
            </div>
            {actions ? (
                <div className="flex flex-wrap items-center gap-2 sm:shrink-0">
                    {actions}
                </div>
            ) : null}
        </div>
    );
}
