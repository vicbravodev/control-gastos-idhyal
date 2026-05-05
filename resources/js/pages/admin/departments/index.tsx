import { Head, Link, router } from '@inertiajs/react';
import { Building2, Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';
import DepartmentController from '@/actions/App/Http/Controllers/Admin/DepartmentController';
import ConfirmationDialog from '@/components/confirmation-dialog';
import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import { TableToolbar } from '@/components/table-toolbar';
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

type DepartmentRow = {
    id: number;
    code: string;
    name: string;
    is_active: boolean;
    position: number;
    users_count: number;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Departamentos', href: DepartmentController.index.url() },
];

export default function AdminDepartmentsIndex({
    departments,
    filters,
}: {
    departments: DepartmentRow[];
    filters: Record<string, string>;
}) {
    const [deleteTarget, setDeleteTarget] = useState<DepartmentRow | null>(
        null,
    );
    const [deleting, setDeleting] = useState(false);

    function handleConfirmDelete() {
        if (!deleteTarget || deleteTarget.users_count > 0) {
            return;
        }

        setDeleting(true);
        router.delete(DepartmentController.destroy.url(deleteTarget.id), {
            preserveScroll: true,
            onFinish: () => {
                setDeleting(false);
                setDeleteTarget(null);
            },
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Departamentos" />
            <div className="relative flex animate-fade-in flex-col gap-4 overflow-hidden p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Departamentos"
                        description="Catálogo de áreas o departamentos. Permite seguimiento de gastos por área."
                    />
                    <Button asChild>
                        <Link href={DepartmentController.create.url()}>
                            Nuevo departamento
                        </Link>
                    </Button>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Listado</CardTitle>
                    </CardHeader>
                    <div className="px-6 pb-4">
                        <TableToolbar
                            currentUrl={DepartmentController.index.url()}
                            filters={filters}
                            searchPlaceholder="Buscar por nombre o código…"
                        />
                    </div>
                    <CardContent>
                        {departments.length === 0 ? (
                            <EmptyState
                                icon={Building2}
                                title="Sin departamentos"
                                description="Cree departamentos para agrupar usuarios y rastrear gastos por área."
                            />
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Código</TableHead>
                                        <TableHead>Nombre</TableHead>
                                        <TableHead className="text-right">
                                            Orden
                                        </TableHead>
                                        <TableHead>Estado</TableHead>
                                        <TableHead className="text-right">
                                            Usuarios
                                        </TableHead>
                                        <TableHead className="w-[100px] text-right">
                                            Acciones
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {departments.map((d) => (
                                        <TableRow key={d.id}>
                                            <TableCell className="font-mono text-sm">
                                                {d.code}
                                            </TableCell>
                                            <TableCell>{d.name}</TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {d.position}
                                            </TableCell>
                                            <TableCell>
                                                <span
                                                    className={
                                                        d.is_active
                                                            ? 'text-emerald-700'
                                                            : 'text-muted-foreground'
                                                    }
                                                >
                                                    {d.is_active
                                                        ? 'Activo'
                                                        : 'Inactivo'}
                                                </span>
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {d.users_count}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={DepartmentController.edit.url(
                                                                d.id,
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
                                                            d.users_count > 0
                                                        }
                                                        onClick={() =>
                                                            setDeleteTarget(d)
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
                title="Eliminar departamento"
                description="¿Eliminar este departamento? Solo es posible si no tiene usuarios asignados."
                confirmLabel="Eliminar"
                variant="destructive"
                processing={deleting}
                onConfirm={handleConfirmDelete}
            />
        </AppLayout>
    );
}
