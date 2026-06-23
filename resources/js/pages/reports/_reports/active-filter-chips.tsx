import { X } from 'lucide-react';

import { Button } from '@/components/ui/button';

import type { FilterKey } from './types';

type Props = {
    chips: Array<{ key: FilterKey; label: string }>;
    onRemove: (key: FilterKey) => void;
    onClearAll: () => void;
};

export function ActiveFilterChips({ chips, onRemove, onClearAll }: Props) {
    if (chips.length === 0) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-center gap-2">
            {chips.map((chip) => (
                <span
                    key={chip.key}
                    className="inline-flex items-center gap-1.5 rounded-full border border-[var(--brand-blue-200)] bg-[var(--brand-blue-50)] py-1 pl-3 pr-1 text-xs font-medium text-[var(--brand-blue-800)]"
                >
                    {chip.label}
                    <button
                        type="button"
                        onClick={() => onRemove(chip.key)}
                        className="inline-flex size-4 items-center justify-center rounded-full text-[var(--brand-blue-700)] hover:bg-[var(--brand-blue-200)]"
                        aria-label={`Quitar filtro ${chip.label}`}
                    >
                        <X className="size-3" />
                    </button>
                </span>
            ))}
            <Button variant="ghost" size="sm" onClick={onClearAll}>
                Limpiar todo
            </Button>
        </div>
    );
}
