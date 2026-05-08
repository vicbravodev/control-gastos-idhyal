import { Head, Link, router } from '@inertiajs/react';
import { Layers3, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

import ExpenseConceptController from '@/actions/App/Http/Controllers/ExpenseConcepts/ExpenseConceptController';
import ConfirmationDialog from '@/components/confirmation-dialog';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/idhyal';
import { TableToolbar } from '@/components/table-toolbar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type ConceptRow = {
    id: number;
    name: string;
    is_active: boolean;
    sort_order: number;
    expense_requests_count: number;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    {
        title: 'Conceptos de gasto',
        href: ExpenseConceptController.index.url(),
    },
];

export default function ExpenseConceptsIndex({
    concepts,
    filters,
}: {
    concepts: ConceptRow[];
    filters: Record<string, string>;
}) {
    const [deleteTarget, setDeleteTarget] = useState<ConceptRow | null>(null);
    const [deleting, setDeleting] = useState(false);

    function handleConfirmDelete() {
        if (!deleteTarget) {
            return;
        }

        setDeleting(true);
        router.delete(ExpenseConceptController.destroy.url(deleteTarget.id), {
            preserveScroll: true,
            onFinish: () => {
                setDeleting(false);
                setDeleteTarget(null);
            },
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Conceptos de gasto" />
            <div className="flex animate-fade-in flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    eyebrow="Administración"
                    title="Conceptos de gasto"
                    subtitle="Catálogo normalizado para clasificar solicitudes. Los solicitantes eligen de la lista; puedes desactivar sin borrar el historial."
                    actions={
                        <Button asChild>
                            <Link href={ExpenseConceptController.create.url()}>
                                <Plus />
                                Nuevo concepto
                            </Link>
                        </Button>
                    }
                />

                <div className="rounded-xl border border-border bg-card p-3.5">
                    <TableToolbar
                        currentUrl={ExpenseConceptController.index.url()}
                        filters={filters}
                        searchPlaceholder="Buscar por nombre…"
                        filterDefinitions={[
                            {
                                key: 'active',
                                label: 'Estado',
                                options: [
                                    { value: '1', label: 'Activo' },
                                    { value: '0', label: 'Inactivo' },
                                ],
                                allLabel: 'Todos',
                            },
                        ]}
                    />
                </div>

                <div className="overflow-hidden rounded-xl border border-border bg-card">
                    {concepts.length === 0 ? (
                        <EmptyState
                            icon={Layers3}
                            title="Sin conceptos"
                            description="Crea el primer concepto para que las solicitudes usen el catálogo."
                        />
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-[var(--card-soft)]">
                                    <TableHead className="w-[48%]">
                                        Nombre
                                    </TableHead>
                                    <TableHead>Orden</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead className="text-right">
                                        Solicitudes
                                    </TableHead>
                                    <TableHead className="w-[120px] text-right">
                                        Acciones
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {concepts.map((row) => (
                                    <TableRow key={row.id}>
                                        <TableCell className="font-medium">
                                            {row.name}
                                        </TableCell>
                                        <TableCell className="t-num text-muted-foreground">
                                            {row.sort_order}
                                        </TableCell>
                                        <TableCell>
                                            {row.is_active ? (
                                                <span className="inline-flex items-center rounded-full bg-[var(--success-bg)] px-2.5 py-0.5 text-xs font-semibold text-[var(--success-fg)]">
                                                    Activo
                                                </span>
                                            ) : (
                                                <Badge variant="secondary">
                                                    Inactivo
                                                </Badge>
                                            )}
                                        </TableCell>
                                        <TableCell className="t-num text-right">
                                            {row.expense_requests_count}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    asChild
                                                >
                                                    <Link
                                                        href={ExpenseConceptController.edit.url(
                                                            row.id,
                                                        )}
                                                    >
                                                        <Pencil className="size-4" />
                                                        <span className="sr-only">
                                                            Editar
                                                        </span>
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    disabled={
                                                        row.expense_requests_count >
                                                        0
                                                    }
                                                    onClick={() =>
                                                        setDeleteTarget(row)
                                                    }
                                                >
                                                    <Trash2 className="size-4" />
                                                    <span className="sr-only">
                                                        Eliminar
                                                    </span>
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </div>
            </div>
            <ConfirmationDialog
                open={deleteTarget !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeleteTarget(null);
                    }
                }}
                title="Eliminar concepto"
                description="¿Eliminar este concepto? Solo es posible si no hay solicitudes vinculadas."
                confirmLabel="Eliminar"
                variant="destructive"
                processing={deleting}
                onConfirm={handleConfirmDelete}
            />
        </AppLayout>
    );
}
