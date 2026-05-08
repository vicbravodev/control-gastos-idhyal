import { Search, X } from 'lucide-react';
import type { ChangeEvent, ReactNode } from 'react';

import { cn } from '@/lib/utils';

type FilterBarProps = {
    search?: {
        value: string;
        placeholder?: string;
        onChange: (value: string) => void;
    };
    children?: ReactNode;
    onClear?: () => void;
    className?: string;
};

export function FilterBar({
    search,
    children,
    onClear,
    className,
}: FilterBarProps) {
    return (
        <div
            className={cn(
                'flex flex-wrap items-center gap-2 rounded-xl border border-border bg-card p-3.5',
                className,
            )}
        >
            {search ? (
                <div className="relative flex min-w-[200px] flex-1 items-center sm:max-w-[280px]">
                    <Search
                        aria-hidden
                        className="pointer-events-none absolute left-3 size-4 text-muted-foreground"
                    />
                    <input
                        type="search"
                        className="h-9 w-full rounded-md border border-border-strong bg-card pr-3 pl-9 text-sm text-foreground placeholder:text-muted-foreground focus:border-[var(--brand-blue-400)] focus:ring-2 focus:ring-[var(--brand-blue-50)] focus:outline-none"
                        placeholder={search.placeholder ?? 'Buscar…'}
                        value={search.value}
                        onChange={(e: ChangeEvent<HTMLInputElement>) =>
                            search.onChange(e.target.value)
                        }
                    />
                </div>
            ) : null}
            {children}
            {onClear ? (
                <button
                    type="button"
                    onClick={onClear}
                    className="ml-auto inline-flex h-8 items-center gap-1.5 rounded-md px-2.5 text-xs font-medium text-muted-foreground hover:bg-muted hover:text-foreground"
                >
                    <X className="size-3.5" aria-hidden />
                    Limpiar
                </button>
            ) : null}
        </div>
    );
}
