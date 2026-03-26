import { Head, Link, router } from '@inertiajs/react';
import { Layers3, Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';
import ExpenseConceptController from '@/actions/App/Http/Controllers/ExpenseConcepts/ExpenseConceptController';
import ConfirmationDialog from '@/components/confirmation-dialog';
import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import { TableToolbar } from '@/components/table-toolbar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
            <div className="flex flex-col gap-4 p-4 animate-fade-in">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Conceptos de gasto"
                        description="Catálogo normalizado para clasificar solicitudes. Los solicitantes eligen de la lista; puedes desactivar sin borrar el historial."
                    />
                    <Button asChild>
                        <Link href={ExpenseConceptController.create.url()}>
                            Nuevo concepto
                        </Link>
                    </Button>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Listado</CardTitle>
                    </CardHeader>
                    <div className="px-6 pb-4">
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
                    <CardContent>
                        {concepts.length === 0 ? (
                            <EmptyState
                                icon={Layers3}
                                title="Sin conceptos"
                                description="Crea el primer concepto para que las solicitudes usen el catálogo."
                            />
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
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
                                            <TableCell className="tabular-nums text-muted-foreground">
                                                {row.sort_order}
                                            </TableCell>
                                            <TableCell>
                                                {row.is_active ? (
                                                    <Badge>Activo</Badge>
                                                ) : (
                                                    <Badge variant="secondary">
                                                        Inactivo
                                                    </Badge>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
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
                    </CardContent>
                </Card>
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
