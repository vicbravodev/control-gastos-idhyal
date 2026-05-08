import { Head, Link, router } from '@inertiajs/react';
import { ChevronRight, Pencil, Plus, ShieldCheck, Trash2 } from 'lucide-react';
import { useState } from 'react';

import ApprovalPolicyController from '@/actions/App/Http/Controllers/ApprovalPolicies/ApprovalPolicyController';
import ConfirmationDialog from '@/components/confirmation-dialog';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/idhyal';
import { TableToolbar } from '@/components/table-toolbar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type PolicyRow = {
    id: number;
    document_type: string;
    document_type_label: string;
    name: string;
    version: number;
    applies_to_label: string | null;
    chain_summary: string;
    effective_from: string | null;
    effective_to: string | null;
    is_active: boolean;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    {
        title: 'Políticas de aprobación',
        href: ApprovalPolicyController.index.url(),
    },
];

function formatDateRange(from: string | null, to: string | null): string {
    if (!from && !to) {
        return 'Sin límite';
    }

    if (from && !to) {
        return `Desde ${from}`;
    }

    if (!from && to) {
        return `Hasta ${to}`;
    }

    return `${from} → ${to}`;
}

export default function ApprovalPoliciesIndex({
    policies,
    filters,
}: {
    policies: PolicyRow[];
    filters: Record<string, string>;
}) {
    const [deleteTarget, setDeleteTarget] = useState<PolicyRow | null>(null);
    const [deleting, setDeleting] = useState(false);

    function handleConfirmDelete() {
        if (!deleteTarget) {
            return;
        }

        setDeleting(true);
        router.delete(ApprovalPolicyController.destroy.url(deleteTarget.id), {
            preserveScroll: true,
            onFinish: () => {
                setDeleting(false);
                setDeleteTarget(null);
            },
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Políticas de aprobación" />
            <div className="flex animate-fade-in flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    eyebrow="Administración"
                    title="Políticas de aprobación"
                    subtitle="Configura las cadenas de aprobación para solicitudes de gasto y vacaciones."
                    actions={
                        <Button asChild>
                            <Link href={ApprovalPolicyController.create.url()}>
                                <Plus />
                                Nueva política
                            </Link>
                        </Button>
                    }
                />
                <div className="rounded-xl border border-border bg-card p-3.5">
                    <TableToolbar
                        currentUrl={ApprovalPolicyController.index.url()}
                        filters={filters}
                        searchPlaceholder="Buscar por nombre…"
                    />
                </div>
                {policies.length === 0 ? (
                    <div className="overflow-hidden rounded-xl border border-border bg-card">
                        <EmptyState
                            icon={ShieldCheck}
                            title="Sin políticas"
                            description="No hay políticas de aprobación configuradas."
                            action={
                                <Button asChild size="sm">
                                    <Link
                                        href={ApprovalPolicyController.create.url()}
                                    >
                                        <Plus />
                                        Crear primera política
                                    </Link>
                                </Button>
                            }
                        />
                    </div>
                ) : (
                    <div className="flex flex-col gap-3">
                        {policies.map((policy) => {
                            const chainSteps = policy.chain_summary
                                ? policy.chain_summary
                                      .split(/\s*[→>]\s*|\s*->\s*/g)
                                      .filter(Boolean)
                                : [];

                            return (
                                <article
                                    key={policy.id}
                                    className="flex flex-col gap-4 rounded-xl border border-border bg-card p-5"
                                >
                                    <div className="flex flex-wrap items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                                <span className="t-folio font-semibold text-[var(--brand-blue-700)]">
                                                    POL-{policy.id}
                                                </span>
                                                <Badge
                                                    variant="secondary"
                                                    className="bg-[var(--brand-blue-50)] text-[var(--brand-blue-700)]"
                                                >
                                                    v{policy.version}
                                                </Badge>
                                                {policy.is_active ? (
                                                    <span className="inline-flex items-center rounded-full bg-[var(--success-bg)] px-2 py-0.5 text-[11px] font-semibold text-[var(--success-fg)]">
                                                        Activa
                                                    </span>
                                                ) : (
                                                    <Badge variant="outline">
                                                        Inactiva
                                                    </Badge>
                                                )}
                                            </div>
                                            <h3 className="mt-1 text-base font-semibold tracking-[-0.01em]">
                                                {policy.name}
                                            </h3>
                                            <div className="mt-1 flex flex-wrap gap-x-4 gap-y-0.5 text-xs text-muted-foreground">
                                                <span>
                                                    <strong className="font-semibold text-foreground">
                                                        Documento:
                                                    </strong>{' '}
                                                    {policy.document_type_label}
                                                </span>
                                                <span>
                                                    <strong className="font-semibold text-foreground">
                                                        Solicitante:
                                                    </strong>{' '}
                                                    {policy.applies_to_label ??
                                                        'Todos'}
                                                </span>
                                                <span>
                                                    <strong className="font-semibold text-foreground">
                                                        Vigencia:
                                                    </strong>{' '}
                                                    {formatDateRange(
                                                        policy.effective_from,
                                                        policy.effective_to,
                                                    )}
                                                </span>
                                            </div>
                                        </div>
                                        <div className="flex shrink-0 gap-1">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={ApprovalPolicyController.edit.url(
                                                        policy.id,
                                                    )}
                                                >
                                                    <Pencil />
                                                    Editar
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onClick={() =>
                                                    setDeleteTarget(policy)
                                                }
                                            >
                                                <Trash2 className="size-4 text-destructive" />
                                                <span className="sr-only">
                                                    Eliminar
                                                </span>
                                            </Button>
                                        </div>
                                    </div>
                                    {chainSteps.length > 0 ? (
                                        <div className="rounded-md border border-border bg-[var(--card-soft)] px-3 py-2.5">
                                            <p className="t-eyebrow mb-2">
                                                Cadena
                                            </p>
                                            <div className="flex flex-wrap items-center gap-1.5">
                                                {chainSteps.map((step, idx) => (
                                                    <div
                                                        key={`${policy.id}-${idx}`}
                                                        className="flex items-center gap-1.5"
                                                    >
                                                        <span className="inline-flex items-center gap-1.5 rounded-full bg-[var(--brand-blue-50)] px-2.5 py-1 text-xs font-semibold text-[var(--brand-blue-700)]">
                                                            <span className="grid size-4 place-items-center rounded-full bg-[var(--brand-blue-100)] text-[10px]">
                                                                {idx + 1}
                                                            </span>
                                                            {step.trim()}
                                                        </span>
                                                        {idx <
                                                        chainSteps.length -
                                                            1 ? (
                                                            <ChevronRight
                                                                className="size-3.5 text-muted-foreground"
                                                                aria-hidden
                                                            />
                                                        ) : null}
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    ) : null}
                                </article>
                            );
                        })}
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
                title="Eliminar política"
                description="¿Eliminar esta política? Esta acción no se puede deshacer."
                confirmLabel="Eliminar"
                variant="destructive"
                processing={deleting}
                onConfirm={handleConfirmDelete}
            />
        </AppLayout>
    );
}
