import { Head, Link, router } from '@inertiajs/react';
import { Map, MapPin, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

import StateController from '@/actions/App/Http/Controllers/Admin/StateController';
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

type StateRow = {
    id: number;
    code: string;
    name: string;
    region: { id: number; name: string; code: string } | null;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Estados', href: StateController.index.url() },
];

function regionLabel(r: StateRow['region']): string {
    if (!r) {
        return '—';
    }

    return r.name ?? r.code ?? `Región #${r.id}`;
}

export default function AdminStatesIndex({
    states,
    filters,
    regions,
}: {
    states: StateRow[];
    filters: Record<string, string>;
    regions: { value: string; label: string }[];
}) {
    const [deleteTarget, setDeleteTarget] = useState<StateRow | null>(null);
    const [deleting, setDeleting] = useState(false);

    function handleConfirmDelete() {
        if (!deleteTarget) {
            return;
        }

        setDeleting(true);
        router.delete(StateController.destroy.url(deleteTarget.id), {
            preserveScroll: true,
            onFinish: () => {
                setDeleting(false);
                setDeleteTarget(null);
            },
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Estados" />
            <div className="flex animate-fade-in flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    eyebrow="Administración"
                    title="Estados"
                    subtitle="Entidades federativas u homólogas, agrupadas bajo una región."
                    actions={
                        <Button asChild>
                            <Link href={StateController.create.url()}>
                                <Plus />
                                Nuevo estado
                            </Link>
                        </Button>
                    }
                />
                <div className="rounded-xl border border-border bg-card p-3.5">
                    <TableToolbar
                        currentUrl={StateController.index.url()}
                        filters={filters}
                        searchPlaceholder="Buscar por nombre o código…"
                        filterDefinitions={[
                            {
                                key: 'region',
                                label: 'Región',
                                options: regions,
                                allLabel: 'Todas las regiones',
                            },
                        ]}
                    />
                </div>
                <div className="overflow-hidden rounded-xl border border-border bg-card">
                    {states.length === 0 ? (
                        <EmptyState
                            icon={Map}
                            title="Sin estados"
                            description="Cree estados vinculados a una región existente."
                        />
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-[var(--card-soft)]">
                                    <TableHead>Código</TableHead>
                                    <TableHead>Nombre</TableHead>
                                    <TableHead>Región</TableHead>
                                    <TableHead className="w-[100px] text-right">
                                        Acciones
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {states.map((s) => (
                                    <TableRow key={s.id}>
                                        <TableCell className="t-folio">
                                            {s.code}
                                        </TableCell>
                                        <TableCell className="font-medium">
                                            <span className="inline-flex items-center gap-2">
                                                <MapPin
                                                    className="size-4 text-[var(--brand-gold-700)]"
                                                    aria-hidden
                                                />
                                                {s.name}
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            {s.region ? (
                                                <Badge
                                                    variant="secondary"
                                                    className="bg-[var(--brand-blue-50)] text-[var(--brand-blue-700)]"
                                                >
                                                    {regionLabel(s.region)}
                                                </Badge>
                                            ) : (
                                                <span className="text-muted-foreground">
                                                    —
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    asChild
                                                >
                                                    <Link
                                                        href={StateController.edit.url(
                                                            s.id,
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
                                                    onClick={() =>
                                                        setDeleteTarget(s)
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
                title="Eliminar estado"
                description="¿Eliminar este estado? Los usuarios perderán la asignación de estado."
                confirmLabel="Eliminar"
                variant="destructive"
                processing={deleting}
                onConfirm={handleConfirmDelete}
            />
        </AppLayout>
    );
}
