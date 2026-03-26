import { router } from '@inertiajs/react';
import { Search, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export type FilterOption = {
    label: string;
    value: string;
};

export type FilterDefinition = {
    key: string;
    label: string;
    options: FilterOption[];
    allLabel?: string;
};

type TableToolbarProps = {
    /** Current page URL for Inertia router.get */
    currentUrl: string;
    /** Current filters from query string (server-provided) */
    filters: Record<string, string>;
    searchKey?: string;
    searchPlaceholder?: string;
    filterDefinitions?: FilterDefinition[];
};

const DEBOUNCE_MS = 300;

export function TableToolbar({
    currentUrl,
    filters,
    searchKey = 'search',
    searchPlaceholder = 'Buscar\u2026',
    filterDefinitions = [],
}: TableToolbarProps) {
    const [search, setSearch] = useState(filters[searchKey] ?? '');
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const isFirstRender = useRef(true);

    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }

        if (debounceRef.current) {
            clearTimeout(debounceRef.current);
        }

        debounceRef.current = setTimeout(() => {
            applyFilters({ [searchKey]: search || undefined });
        }, DEBOUNCE_MS);

        return () => {
            if (debounceRef.current) {
                clearTimeout(debounceRef.current);
            }
        };
    }, [search]); // eslint-disable-line react-hooks/exhaustive-deps

    function applyFilters(overrides: Record<string, string | undefined>) {
        const params: Record<string, string> = {};

        if (filters[searchKey] && !overrides.hasOwnProperty(searchKey)) {
            params[searchKey] = filters[searchKey];
        }
        if (overrides[searchKey]) {
            params[searchKey] = overrides[searchKey];
        }

        for (const def of filterDefinitions) {
            if (overrides.hasOwnProperty(def.key)) {
                if (overrides[def.key]) {
                    params[def.key] = overrides[def.key];
                }
            } else if (filters[def.key]) {
                params[def.key] = filters[def.key];
            }
        }

        router.get(currentUrl, params, {
            preserveState: true,
            replace: true,
            preserveScroll: true,
        });
    }

    function handleFilterChange(key: string, value: string) {
        applyFilters({ [key]: value === '__all__' ? undefined : value });
    }

    function clearAll() {
        setSearch('');
        router.get(currentUrl, {}, {
            preserveState: true,
            replace: true,
            preserveScroll: true,
        });
    }

    const hasActiveFilters =
        search.length > 0 ||
        filterDefinitions.some((def) => Boolean(filters[def.key]));

    return (
        <div className="flex flex-wrap items-center gap-2">
            <div className="relative max-w-xs flex-1">
                <Search className="pointer-events-none absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    placeholder={searchPlaceholder}
                    className="pl-8"
                    autoComplete="off"
                    spellCheck={false}
                />
            </div>
            {filterDefinitions.map((def) => (
                <Select
                    key={def.key}
                    value={filters[def.key] ?? '__all__'}
                    onValueChange={(v) => handleFilterChange(def.key, v)}
                >
                    <SelectTrigger className="w-auto min-w-[140px]">
                        <SelectValue placeholder={def.label} />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="__all__">
                            {def.allLabel ?? `Todos`}
                        </SelectItem>
                        {def.options.map((opt) => (
                            <SelectItem key={opt.value} value={opt.value}>
                                {opt.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            ))}
            {hasActiveFilters && (
                <Button
                    variant="ghost"
                    size="sm"
                    onClick={clearAll}
                    className="h-9 px-2"
                >
                    <X className="mr-1 size-4" />
                    Limpiar
                </Button>
            )}
        </div>
    );
}
