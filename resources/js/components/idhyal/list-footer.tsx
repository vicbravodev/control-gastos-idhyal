import type { ReactNode } from 'react';

import { PaginationNav } from '@/components/pagination-nav';
import { cn } from '@/lib/utils';

export type ListFooterPaginator = {
    from: number | null;
    to: number | null;
    total: number;
    current_page: number;
    last_page: number;
    links: { url: string | null; label: string; active: boolean }[];
};

type ListFooterProps = {
    paginator: ListFooterPaginator;
    /** Singular label for the entity, used in "Mostrando X de Y solicitudes". */
    label: string;
    /** Override the left-side hint entirely. */
    hint?: ReactNode;
    className?: string;
};

export function ListFooter({
    paginator,
    label,
    hint,
    className,
}: ListFooterProps) {
    if (paginator.total === 0) {
        return null;
    }

    const from = paginator.from ?? 0;
    const to = paginator.to ?? 0;
    const range = from === to ? `${from}` : `${from}–${to}`;

    return (
        <div className={cn('idh-list-footer', className)}>
            <span>
                {hint ?? (
                    <>
                        Mostrando{' '}
                        <span className="font-semibold text-foreground tabular-nums">
                            {range}
                        </span>{' '}
                        de{' '}
                        <span className="font-semibold text-foreground tabular-nums">
                            {paginator.total}
                        </span>{' '}
                        {label}
                    </>
                )}
            </span>
            <PaginationNav
                links={paginator.links}
                currentPage={paginator.current_page}
                lastPage={paginator.last_page}
            />
        </div>
    );
}
