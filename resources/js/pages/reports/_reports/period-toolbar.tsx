import { CalendarRange, Filter, Pencil, RefreshCw } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';

import type { PeriodInfo, PeriodPreset } from './types';

type Props = {
    period: PeriodInfo;
    presets: PeriodPreset[];
    compare: boolean;
    activeFiltersCount: number;
    customFrom: string;
    customTo: string;
    onPeriodChange: (id: string) => void;
    onCompareChange: (next: boolean) => void;
    onCustomRangeChange: (from: string, to: string) => void;
    onToggleFilters: () => void;
    onRefresh: () => void;
};

export function PeriodToolbar({
    period,
    presets,
    compare,
    activeFiltersCount,
    customFrom,
    customTo,
    onPeriodChange,
    onCompareChange,
    onCustomRangeChange,
    onToggleFilters,
    onRefresh,
}: Props) {
    const [editOpen, setEditOpen] = useState(false);
    const [draftFrom, setDraftFrom] = useState(customFrom);
    const [draftTo, setDraftTo] = useState(customTo);

    const compareLabel =
        period.id === 'mtd'
            ? 'mes anterior'
            : period.id === 'qtd'
              ? 'trimestre anterior'
              : period.id === 'ytd'
                ? 'año anterior'
                : 'periodo anterior';

    return (
        <div className="flex flex-wrap items-center gap-3 rounded-xl border border-border bg-card p-3.5">
            <div className="flex flex-wrap items-center gap-2">
                <span className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                    Periodo
                </span>
                <div className="flex flex-wrap items-center gap-1">
                    {presets
                        .filter((p) => p.id !== 'custom')
                        .map((p) => (
                            <button
                                key={p.id}
                                type="button"
                                onClick={() => onPeriodChange(p.id)}
                                className={cn(
                                    'rounded-md px-2.5 py-1 text-xs font-medium transition-colors',
                                    period.id === p.id
                                        ? 'bg-[var(--brand-blue-50)] text-[var(--brand-blue-800)] ring-1 ring-[var(--brand-blue-300)]'
                                        : 'text-muted-foreground hover:bg-[var(--card-soft)] hover:text-foreground',
                                )}
                            >
                                {p.label}
                            </button>
                        ))}
                </div>
                <Popover open={editOpen} onOpenChange={setEditOpen}>
                    <PopoverTrigger asChild>
                        <button
                            type="button"
                            className={cn(
                                'inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-medium transition-colors',
                                period.id === 'custom'
                                    ? 'bg-[var(--brand-blue-50)] text-[var(--brand-blue-800)] ring-1 ring-[var(--brand-blue-300)]'
                                    : 'border border-dashed border-border text-muted-foreground hover:border-[var(--brand-blue-300)] hover:text-foreground',
                            )}
                        >
                            <CalendarRange className="size-3.5" aria-hidden />
                            {period.range_label || 'Rango personalizado'}
                            <Pencil className="size-3 opacity-60" aria-hidden />
                        </button>
                    </PopoverTrigger>
                    <PopoverContent align="start" className="w-auto p-4">
                        <div className="flex flex-col gap-3 min-w-[280px]">
                            <div className="grid gap-1.5">
                                <Label htmlFor="custom-from">Desde</Label>
                                <DatePicker
                                    id="custom-from"
                                    value={draftFrom}
                                    onChange={setDraftFrom}
                                    disableFuture
                                />
                            </div>
                            <div className="grid gap-1.5">
                                <Label htmlFor="custom-to">Hasta</Label>
                                <DatePicker
                                    id="custom-to"
                                    value={draftTo}
                                    onChange={setDraftTo}
                                    disableFuture
                                />
                            </div>
                            <div className="flex justify-end gap-2">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => setEditOpen(false)}
                                >
                                    Cancelar
                                </Button>
                                <Button
                                    size="sm"
                                    onClick={() => {
                                        onCustomRangeChange(draftFrom, draftTo);
                                        setEditOpen(false);
                                    }}
                                >
                                    Aplicar
                                </Button>
                            </div>
                        </div>
                    </PopoverContent>
                </Popover>
            </div>

            <div className="ml-auto flex flex-wrap items-center gap-3">
                <label className="inline-flex cursor-pointer items-center gap-2 text-xs text-muted-foreground">
                    <input
                        type="checkbox"
                        className="size-4 rounded border-border accent-[var(--brand-blue-600)]"
                        checked={compare}
                        onChange={(e) => onCompareChange(e.target.checked)}
                    />
                    <span>Comparar vs {compareLabel}</span>
                </label>
                <Button
                    variant="outline"
                    size="sm"
                    onClick={onToggleFilters}
                    className="gap-2"
                >
                    <Filter className="size-3.5" />
                    Filtros
                    {activeFiltersCount > 0 ? (
                        <span className="rounded-full bg-[var(--brand-blue-100)] px-1.5 text-[10px] font-semibold text-[var(--brand-blue-800)]">
                            {activeFiltersCount}
                        </span>
                    ) : null}
                </Button>
                <Button
                    variant="ghost"
                    size="icon"
                    onClick={onRefresh}
                    title="Refrescar"
                >
                    <RefreshCw className="size-4" />
                </Button>
            </div>
        </div>
    );
}
