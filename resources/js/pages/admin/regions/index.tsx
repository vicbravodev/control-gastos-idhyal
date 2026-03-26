import { Head, Link, router } from '@inertiajs/react';
import { MapPinned, Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';
import RegionController from '@/actions/App/Http/Controllers/Admin/RegionController';
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

type RegionRow = {
    id: number;
    code: string;
    name: string;
    states_count: number;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Regiones', href: RegionController.index.url() },
];

export default function AdminRegionsIndex({
    regions,
    filters,
}: {
    regions: RegionRow[];
    filters: Record<string, string>;
}) {
    const [deleteTarget, setDeleteTarget] = useState<RegionRow | null>(null);
    const [deleting, setDeleting] = useState(false);

    function handleConfirmDelete() {
        if (!deleteTarget || deleteTarget.states_count > 0) {
            return;
        }

        setDeleting(true);
        router.delete(RegionController.destroy.url(deleteTarget.id), {
            preserveScroll: true,
            onFinish: () => {
                setDeleting(false);
                setDeleteTarget(null);
            },
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Regiones" />
            <div className="relative flex animate-fade-in flex-col gap-4 overflow-hidden p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Regiones"
                        description="Catálogo territorial de primer nivel. Los estados se asocian a una región."
                    />
                    <Button asChild>
                        <Link href={RegionController.create.url()}>
                            Nueva región
                        </Link>
                    </Button>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Listado</CardTitle>
                    </CardHeader>
                    <div className="px-6 pb-4">
                        <TableToolbar
                            currentUrl={RegionController.index.url()}
                            filters={filters}
                            searchPlaceholder="Buscar por nombre o código…"
                        />
                    </div>
                    <CardContent>
                        {regions.length === 0 ? (
                            <EmptyState
                                icon={MapPinned}
                                title="Sin regiones"
                                description="Cree regiones para asignar estados y usuarios."
                            />
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Código</TableHead>
                                        <TableHead>Nombre</TableHead>
                                        <TableHead className="text-right">
                                            Estados
                                        </TableHead>
                                        <TableHead className="w-[100px] text-right">
                                            Acciones
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {regions.map((r) => (
                                        <TableRow key={r.id}>
                                            <TableCell className="font-mono text-sm">
                                                {r.code}
                                            </TableCell>
                                            <TableCell>{r.name}</TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {r.states_count}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={RegionController.edit.url(
                                                                r.id,
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
                                                            r.states_count > 0
                                                        }
                                                        onClick={() =>
                                                            setDeleteTarget(r)
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
                title="Eliminar región"
                description="¿Eliminar esta región? Solo es posible si no tiene estados asociados."
                confirmLabel="Eliminar"
                variant="destructive"
                processing={deleting}
                onConfirm={handleConfirmDelete}
            />
        </AppLayout>
    );
}
