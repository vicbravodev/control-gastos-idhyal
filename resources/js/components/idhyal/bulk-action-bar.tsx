import { X } from 'lucide-react';
import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

export type BulkAction = {
    key: string;
    label: ReactNode;
    icon?: ReactNode;
    onClick: () => void;
    danger?: boolean;
    disabled?: boolean;
};

type BulkActionBarProps = {
    count: number;
    label?: (count: number) => ReactNode;
    actions: BulkAction[];
    onClear: () => void;
    className?: string;
};

const defaultLabel = (count: number) =>
    `${count} ${count === 1 ? 'elemento seleccionado' : 'elementos seleccionados'}`;

export function BulkActionBar({
    count,
    label = defaultLabel,
    actions,
    onClear,
    className,
}: BulkActionBarProps) {
    if (count === 0) {
        return null;
    }

    return (
        <div className={cn('idh-bulk-bar', className)}>
            <span className="text-sm font-semibold">{label(count)}</span>
            <span className="sep" />
            {actions.map((action) => (
                <button
                    key={action.key}
                    type="button"
                    onClick={action.onClick}
                    disabled={action.disabled}
                    className={cn(
                        'inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-sm font-medium transition-colors hover:bg-white/12 disabled:cursor-not-allowed disabled:opacity-50',
                        action.danger && 'text-[var(--brand-gold-100)]',
                    )}
                >
                    {action.icon}
                    {action.label}
                </button>
            ))}
            <span className="sep" />
            <button
                type="button"
                onClick={onClear}
                className="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-sm font-medium hover:bg-white/12"
            >
                <X className="size-3.5" aria-hidden />
                Limpiar
            </button>
        </div>
    );
}
