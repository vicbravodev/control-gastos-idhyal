import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

type DetailRowProps = {
    label: ReactNode;
    children: ReactNode;
    className?: string;
};

export function DetailRow({ label, children, className }: DetailRowProps) {
    return (
        <div
            className={cn(
                'grid grid-cols-1 gap-1 border-b border-dashed border-border py-2.5 text-sm last:border-b-0 sm:grid-cols-[160px_1fr] sm:gap-3',
                className,
            )}
        >
            <div className="font-medium text-muted-foreground">{label}</div>
            <div className="font-medium">{children}</div>
        </div>
    );
}
