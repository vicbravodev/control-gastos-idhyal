import { Columns3, LayoutDashboard, List, Table2 } from 'lucide-react';
import type { ReactNode } from 'react';

import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';

import { GROUP_BY_OPTIONS, type GroupBy, type ViewId } from './types';

type Props = {
    view: ViewId;
    onViewChange: (view: ViewId) => void;
    groupBy: GroupBy;
    onGroupByChange: (next: GroupBy) => void;
    totalRows?: number;
    onToggleColumns?: () => void;
    children: ReactNode;
};

export function ViewTabs({
    view,
    onViewChange,
    groupBy,
    onGroupByChange,
    totalRows,
    onToggleColumns,
    children,
}: Props) {
    return (
        <Tabs value={view} onValueChange={(v) => onViewChange(v as ViewId)}>
            <div className="flex flex-wrap items-center justify-between gap-3">
                <TabsList>
                    <TabsTrigger value="resumen">
                        <LayoutDashboard />
                        Resumen
                    </TabsTrigger>
                    <TabsTrigger value="pivote">
                        <Table2 />
                        Por dimensión
                    </TabsTrigger>
                    <TabsTrigger value="detalle">
                        <List />
                        Detalle
                    </TabsTrigger>
                </TabsList>

                <div className="flex items-center gap-2 text-xs text-muted-foreground">
                    {view === 'pivote' && (
                        <>
                            <span>Agrupar por</span>
                            <Select
                                value={groupBy}
                                onValueChange={(v) =>
                                    onGroupByChange(v as GroupBy)
                                }
                            >
                                <SelectTrigger className="h-8 w-[180px] text-xs">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {GROUP_BY_OPTIONS.map((g) => (
                                        <SelectItem key={g.id} value={g.id}>
                                            {g.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </>
                    )}
                    {view === 'detalle' && (
                        <>
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={onToggleColumns}
                            >
                                <Columns3 />
                                Columnas
                            </Button>
                            {totalRows != null && (
                                <span>
                                    {new Intl.NumberFormat('es-MX').format(
                                        totalRows,
                                    )}{' '}
                                    filas
                                </span>
                            )}
                        </>
                    )}
                </div>
            </div>
            {children}
        </Tabs>
    );
}
