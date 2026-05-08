import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

type QuickCardTone = 'blue' | 'gold' | 'muted';

type QuickCardProps = {
    tone?: QuickCardTone;
    icon: ReactNode;
    title: ReactNode;
    description?: ReactNode;
    actions?: ReactNode;
    children?: ReactNode;
    className?: string;
};

const TILE_CLASSES: Record<QuickCardTone, string> = {
    blue: 'bg-[var(--brand-blue-50)] text-[var(--brand-blue-700)]',
    gold: 'bg-[var(--brand-gold-100)] text-[var(--brand-gold-700)]',
    muted: 'bg-muted text-foreground',
};

const BLOB_CLASSES: Record<QuickCardTone, string> = {
    blue: 'idh-blob',
    gold: 'idh-blob idh-blob-gold',
    muted: 'idh-blob idh-blob-muted',
};

export function QuickCard({
    tone = 'blue',
    icon,
    title,
    description,
    actions,
    children,
    className,
}: QuickCardProps) {
    return (
        <div
            className={cn(
                'relative flex min-h-[12.25rem] flex-col gap-3.5 overflow-hidden rounded-[var(--radius-xl)] border border-border bg-card p-6',
                className,
            )}
        >
            <span aria-hidden className={BLOB_CLASSES[tone]} />
            <div className="relative flex items-center gap-3.5">
                <div
                    className={cn(
                        'grid size-11 place-items-center rounded-xl',
                        TILE_CLASSES[tone],
                    )}
                >
                    {icon}
                </div>
                <div className="text-lg font-bold tracking-[-0.01em]">
                    {title}
                </div>
            </div>
            {description ? (
                <div className="relative flex-1 text-sm text-muted-foreground">
                    {description}
                </div>
            ) : null}
            {children ? <div className="relative">{children}</div> : null}
            {actions ? (
                <div className="relative flex flex-wrap gap-2">{actions}</div>
            ) : null}
        </div>
    );
}
