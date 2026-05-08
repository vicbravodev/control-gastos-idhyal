import { Head, Link, router } from '@inertiajs/react';
import { ShieldCheck, Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';
import RoleController from '@/actions/App/Http/Controllers/Admin/RoleController';
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

type RoleRow = {
    id: number;
    slug: string;
    name: string;
    description: string | null;
    users_count: number;
    permissions_count: number;
    can_delete: boolean;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Roles', href: RoleController.index.url() },
];

export default function AdminRolesIndex({
    roles,
    filters,
}: {
    roles: RoleRow[];
    filters: Record<string, string>;
}) {
    const [deleteTarget, setDeleteTarget] = useState<RoleRow | null>(null);
    const [deleting, setDeleting] = useState(false);

    function handleConfirmDelete() {
        if (!deleteTarget || !deleteTarget.can_delete) {
            return;
        }

        setDeleting(true);
        router.delete(RoleController.destroy.url(deleteTarget.id), {
            preserveScroll: true,
            onFinish: () => {
                setDeleting(false);
                setDeleteTarget(null);
            },
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Roles" />
            <div className="relative flex animate-fade-in flex-col gap-4 overflow-hidden p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Roles"
                        description="Defina roles personalizados con permisos granulares para cada parte del sistema."
                    />
                    <Button asChild>
                        <Link href={RoleController.create.url()}>
                            Nuevo rol
                        </Link>
                    </Button>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Listado</CardTitle>
                    </CardHeader>
                    <div className="px-6 pb-4">
                        <TableToolbar
                            currentUrl={RoleController.index.url()}
                            filters={filters}
                            searchPlaceholder="Buscar por nombre o slug…"
                        />
                    </div>
                    <CardContent>
                        {roles.length === 0 ? (
                            <EmptyState
                                icon={ShieldCheck}
                                title="Sin roles"
                                description="Defina roles para asignar permisos granulares a usuarios."
                            />
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Slug</TableHead>
                                        <TableHead>Nombre</TableHead>
                                        <TableHead>Descripción</TableHead>
                                        <TableHead className="text-right">
                                            Usuarios
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Permisos
                                        </TableHead>
                                        <TableHead className="w-[100px] text-right">
                                            Acciones
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {roles.map((role) => (
                                        <TableRow key={role.id}>
                                            <TableCell className="font-mono text-sm">
                                                {role.slug}
                                            </TableCell>
                                            <TableCell>{role.name}</TableCell>
                                            <TableCell className="max-w-xs truncate text-sm text-muted-foreground">
                                                {role.description ?? '—'}
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {role.users_count}
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {role.permissions_count}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={RoleController.edit.url(
                                                                role.id,
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
                                                            !role.can_delete
                                                        }
                                                        onClick={() =>
                                                            setDeleteTarget(
                                                                role,
                                                            )
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
                title="Eliminar rol"
                description="¿Eliminar este rol? Solo es posible si no tiene usuarios ni políticas de aprobación referenciándolo."
                confirmLabel="Eliminar"
                variant="destructive"
                processing={deleting}
                onConfirm={handleConfirmDelete}
            />
        </AppLayout>
    );
}
