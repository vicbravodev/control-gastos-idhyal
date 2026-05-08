import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

type ListTableProps = {
    children: ReactNode;
    className?: string;
    wrapperClassName?: string;
    'aria-label'?: string;
};

export function ListTable({
    children,
    className,
    wrapperClassName,
    'aria-label': ariaLabel,
}: ListTableProps) {
    return (
        <div
            className={cn(
                'overflow-hidden rounded-xl border border-border bg-card',
                wrapperClassName,
            )}
        >
            <div className="relative w-full overflow-auto">
                <table
                    className={cn('idh-table', className)}
                    aria-label={ariaLabel}
                >
                    {children}
                </table>
            </div>
        </div>
    );
}
