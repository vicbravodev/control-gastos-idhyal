import { Filter, RotateCcw, Search, X } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

import type { FilterKey, FilterMap, FilterOptions } from './types';

type Props = {
    filters: FilterMap;
    options: FilterOptions | undefined;
    onChange: (key: FilterKey, value: string) => void;
    onReset: () => void;
    onClose: () => void;
};

const ANY = '__any__';

function selectValue(value: string): string {
    return value === '' ? ANY : value;
}

export function AdvancedFiltersPanel({
    filters,
    options,
    onChange,
    onReset,
    onClose,
}: Props) {
    return (
        <div className="rounded-xl border border-border bg-card">
            <header className="flex items-center gap-2.5 border-b border-border px-5 py-3">
                <Filter className="size-4 text-[var(--brand-blue-600)]" aria-hidden />
                <h2 className="text-sm font-semibold">Filtros avanzados</h2>
                <div className="ml-auto flex items-center gap-1">
                    <Button variant="ghost" size="sm" onClick={onReset}>
                        <RotateCcw />
                        Restablecer
                    </Button>
                    <Button variant="ghost" size="icon" onClick={onClose}>
                        <X className="size-4" />
                    </Button>
                </div>
            </header>
            <div className="grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-3">
                <div className="grid gap-1.5">
                    <Label htmlFor="search">Buscar folio</Label>
                    <div className="relative">
                        <Search className="absolute left-2.5 top-2.5 size-4 text-muted-foreground" />
                        <Input
                            id="search"
                            placeholder="SOL-2026-…"
                            className="pl-8"
                            value={filters.search}
                            onChange={(e) => onChange('search', e.target.value)}
                        />
                    </div>
                </div>

                {[
                    { key: 'status' as const, label: 'Estado de solicitud', opts: options?.statuses },
                    { key: 'region_id' as const, label: 'Región', opts: options?.regions },
                    { key: 'state_id' as const, label: 'Estado (geo)', opts: options?.states },
                    { key: 'user_id' as const, label: 'Solicitante', opts: options?.users },
                    { key: 'role_id' as const, label: 'Rol del solicitante', opts: options?.roles },
                    { key: 'expense_concept_id' as const, label: 'Concepto', opts: options?.expense_concepts },
                    { key: 'delivery_method' as const, label: 'Forma de entrega', opts: options?.delivery_methods },
                ].map(({ key, label, opts }) => (
                    <div key={key} className="grid gap-1.5">
                        <Label htmlFor={`filter-${key}`}>{label}</Label>
                        <Select
                            value={selectValue(filters[key])}
                            onValueChange={(v) =>
                                onChange(key, v === ANY ? '' : v)
                            }
                        >
                            <SelectTrigger id={`filter-${key}`}>
                                <SelectValue placeholder="Todos" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={ANY}>Todos</SelectItem>
                                {(opts ?? []).map((opt) => (
                                    <SelectItem
                                        key={opt.value}
                                        value={opt.value}
                                    >
                                        {opt.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                ))}
            </div>
        </div>
    );
}
