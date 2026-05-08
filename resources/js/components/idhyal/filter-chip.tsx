import { X } from 'lucide-react';
import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

type FilterChipProps = {
    label: ReactNode;
    onRemove?: () => void;
    className?: string;
};

export function FilterChip({ label, onRemove, className }: FilterChipProps) {
    return (
        <span
            className={cn(
                'inline-flex items-center gap-1 rounded-full border border-border-strong bg-[var(--card-soft)] py-1 pr-1 pl-3 text-xs text-foreground',
                className,
            )}
        >
            <span>{label}</span>
            {onRemove ? (
                <button
                    type="button"
                    onClick={onRemove}
                    className="grid size-4 place-items-center rounded-full text-muted-foreground hover:bg-muted hover:text-foreground"
                    aria-label="Quitar filtro"
                >
                    <X className="size-3" aria-hidden />
                </button>
            ) : null}
        </span>
    );
}
