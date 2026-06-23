import { StatusBadge } from '@/components/status-badge';
import { formatCentsMx } from '@/lib/money';

import type { StatusBucket } from './types';

type Props = {
    buckets: StatusBucket[];
    activeStatus: string;
    onSelect: (status: string) => void;
};

export function StatusGrid({ buckets, activeStatus, onSelect }: Props) {
    return (
        <div className="grid gap-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7">
            {buckets.map((b) => {
                const isActive = b.status === activeStatus;
                const isEmpty = b.count === 0;

                return (
                    <button
                        type="button"
                        key={b.status}
                        onClick={() =>
                            onSelect(isActive ? '' : b.status)
                        }
                        className={`flex flex-col gap-1.5 rounded-lg border p-3 text-left transition-colors ${
                            isActive
                                ? 'border-[var(--brand-blue-300)] bg-[var(--brand-blue-50)]'
                                : 'border-border bg-card hover:bg-[var(--card-soft)]'
                        } ${isEmpty ? 'opacity-60' : ''}`}
                    >
                        <div className="flex items-center justify-between gap-2">
                            <StatusBadge status={b.status} />
                            <span className="t-num text-base font-bold tabular-nums">
                                {b.count}
                            </span>
                        </div>
                        <div className="t-num text-xs font-semibold text-foreground tabular-nums">
                            {formatCentsMx(b.total_cents)}
                        </div>
                    </button>
                );
            })}
        </div>
    );
}
