import { Head, Link, router } from '@inertiajs/react';
import { ArrowRight, Map, MapPinned, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

import RegionController from '@/actions/App/Http/Controllers/Admin/RegionController';
import ConfirmationDialog from '@/components/confirmation-dialog';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/idhyal';
import { TableToolbar } from '@/components/table-toolbar';
import { Button } from '@/components/ui/button';
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
            <div className="relative flex animate-fade-in flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    eyebrow="Administración"
                    title="Regiones"
                    subtitle="Catálogo territorial de primer nivel. Los estados se asocian a una región."
                    actions={
                        <Button asChild>
                            <Link href={RegionController.create.url()}>
                                <Plus />
                                Nueva región
                            </Link>
                        </Button>
                    }
                />
                <div className="rounded-xl border border-border bg-card p-3.5">
                    <TableToolbar
                        currentUrl={RegionController.index.url()}
                        filters={filters}
                        searchPlaceholder="Buscar por nombre o código…"
                    />
                </div>
                {regions.length === 0 ? (
                    <div className="overflow-hidden rounded-xl border border-border bg-card">
                        <EmptyState
                            icon={MapPinned}
                            title="Sin regiones"
                            description="Cree regiones para asignar estados y usuarios."
                        />
                    </div>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {regions.map((r) => (
                            <article
                                key={r.id}
                                className="flex flex-col gap-3 rounded-xl border border-border bg-card p-5"
                            >
                                <header className="flex items-start gap-3">
                                    <div className="grid size-11 shrink-0 place-items-center rounded-xl bg-[var(--brand-blue-50)] text-[var(--brand-blue-700)]">
                                        <Map className="size-5" aria-hidden />
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <p className="t-folio text-[var(--brand-blue-700)]">
                                            {r.code}
                                        </p>
                                        <h3 className="text-base font-semibold tracking-[-0.01em]">
                                            {r.name}
                                        </h3>
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        disabled={r.states_count > 0}
                                        onClick={() => setDeleteTarget(r)}
                                    >
                                        <Trash2 className="size-4" />
                                        <span className="sr-only">
                                            Eliminar
                                        </span>
                                    </Button>
                                </header>
                                <div className="flex items-center justify-between border-t border-border pt-3 text-sm">
                                    <div className="text-muted-foreground">
                                        Estados:{' '}
                                        <span className="t-num font-semibold text-foreground">
                                            {r.states_count}
                                        </span>
                                    </div>
                                    <Button
                                        variant="brand-soft"
                                        size="sm"
                                        asChild
                                    >
                                        <Link
                                            href={RegionController.edit.url(
                                                r.id,
                                            )}
                                        >
                                            <Pencil />
                                            Editar
                                            <ArrowRight />
                                        </Link>
                                    </Button>
                                </div>
                            </article>
                        ))}
                    </div>
                )}
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
