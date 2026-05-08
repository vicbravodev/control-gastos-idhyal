import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';

export type PermissionItem = {
    id: number;
    slug: string;
    name: string;
    module: string;
    description: string | null;
};

export type PermissionGroup = Record<string, PermissionItem[]>;

const MODULE_LABELS: Record<string, string> = {
    expense_requests: 'Solicitudes de gasto',
    expense_reports: 'Comprobaciones',
    payments: 'Pagos',
    vacation_requests: 'Vacaciones',
    budgets: 'Presupuestos',
    approval_policies: 'Políticas de aprobación',
    vacation_rules: 'Reglas de vacaciones',
    admin: 'Administración',
    reports: 'Reportes',
    system: 'Sistema',
};

export function PermissionMatrix({
    permissions,
    selectedIds,
    onChange,
    readOnly = false,
}: {
    permissions: PermissionGroup;
    selectedIds: number[];
    onChange: (ids: number[]) => void;
    readOnly?: boolean;
}) {
    const selected = new Set(selectedIds);

    function toggle(id: number) {
        if (readOnly) return;
        const next = new Set(selected);
        if (next.has(id)) {
            next.delete(id);
        } else {
            next.add(id);
        }
        onChange(Array.from(next));
    }

    function toggleModule(items: PermissionItem[]) {
        if (readOnly) return;
        const ids = items.map((p) => p.id);
        const allSelected = ids.every((id) => selected.has(id));
        const next = new Set(selected);
        if (allSelected) {
            ids.forEach((id) => next.delete(id));
        } else {
            ids.forEach((id) => next.add(id));
        }
        onChange(Array.from(next));
    }

    function toggleAll() {
        if (readOnly) return;
        const allIds = Object.values(permissions).flat().map((p) => p.id);
        const allSelected = allIds.every((id) => selected.has(id));
        onChange(allSelected ? [] : allIds);
    }

    const totalAll = Object.values(permissions).flat().length;
    const allChecked = totalAll > 0 && totalAll === selected.size;

    return (
        <div className="flex flex-col gap-6">
            <div className="flex items-center gap-2 border-b pb-3">
                <Checkbox
                    id="perm-all"
                    checked={allChecked}
                    onCheckedChange={toggleAll}
                    disabled={readOnly}
                />
                <Label htmlFor="perm-all" className="font-semibold">
                    Marcar / desmarcar todo
                </Label>
            </div>
            {Object.entries(permissions).map(([module, items]) => {
                const moduleIds = items.map((p) => p.id);
                const moduleAllSelected = moduleIds.every((id) =>
                    selected.has(id),
                );
                const moduleSomeSelected = moduleIds.some((id) =>
                    selected.has(id),
                );

                return (
                    <div key={module} className="flex flex-col gap-3">
                        <div className="flex items-center gap-2 border-b pb-1">
                            <Checkbox
                                id={`perm-mod-${module}`}
                                checked={moduleAllSelected}
                                onCheckedChange={() => toggleModule(items)}
                                disabled={readOnly}
                            />
                            <Label
                                htmlFor={`perm-mod-${module}`}
                                className="font-semibold"
                            >
                                {MODULE_LABELS[module] ?? module}
                                {moduleSomeSelected && !moduleAllSelected
                                    ? ` (${moduleIds.filter((id) => selected.has(id)).length}/${moduleIds.length})`
                                    : null}
                            </Label>
                        </div>
                        <div className="grid grid-cols-1 gap-2 pl-6 sm:grid-cols-2">
                            {items.map((p) => (
                                <div
                                    key={p.id}
                                    className="flex items-start gap-2"
                                >
                                    <Checkbox
                                        id={`perm-${p.id}`}
                                        checked={selected.has(p.id)}
                                        onCheckedChange={() => toggle(p.id)}
                                        disabled={readOnly}
                                    />
                                    <div className="flex flex-col">
                                        <Label
                                            htmlFor={`perm-${p.id}`}
                                            className="font-normal"
                                        >
                                            {p.name}
                                        </Label>
                                        <span className="font-mono text-xs text-muted-foreground">
                                            {p.slug}
                                        </span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
