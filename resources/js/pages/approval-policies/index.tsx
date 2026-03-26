import { Head, Link, router } from '@inertiajs/react';
import { Pencil, ShieldCheck, Trash2 } from 'lucide-react';
import { useState } from 'react';
import ApprovalPolicyController from '@/actions/App/Http/Controllers/ApprovalPolicies/ApprovalPolicyController';
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

type PolicyRow = {
    id: number;
    document_type: string;
    document_type_label: string;
    name: string;
    version: number;
    requester_role_name: string | null;
    steps_summary: string;
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
            <div className="flex animate-fade-in flex-col gap-4 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Políticas de aprobación"
                        description="Configura las cadenas de aprobación para solicitudes de gasto y vacaciones."
                    />
                    <Button asChild>
                        <Link href={ApprovalPolicyController.create.url()}>
                            Nueva política
                        </Link>
                    </Button>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Listado</CardTitle>
                    </CardHeader>
                    <div className="px-6 pb-4">
                        <TableToolbar
                            currentUrl={ApprovalPolicyController.index.url()}
                            filters={filters}
                            searchPlaceholder="Buscar por nombre…"
                        />
                    </div>
                    <CardContent>
                        {policies.length === 0 ? (
                            <EmptyState
                                icon={ShieldCheck}
                                title="Sin políticas"
                                description="No hay políticas de aprobación configuradas."
                                action={
                                    <Button asChild size="sm">
                                        <Link
                                            href={ApprovalPolicyController.create.url()}
                                        >
                                            Crear primera política
                                        </Link>
                                    </Button>
                                }
                            />
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nombre</TableHead>
                                        <TableHead>Tipo documento</TableHead>
                                        <TableHead>Rol solicitante</TableHead>
                                        <TableHead>
                                            Cadena de aprobación
                                        </TableHead>
                                        <TableHead>Vigencia</TableHead>
                                        <TableHead>Versión</TableHead>
                                        <TableHead>Estado</TableHead>
                                        <TableHead className="text-right">
                                            Acciones
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {policies.map((policy) => (
                                        <TableRow key={policy.id}>
                                            <TableCell className="font-medium">
                                                {policy.name}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="secondary">
                                                    {policy.document_type_label}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {policy.requester_role_name ??
                                                    'Todos'}
                                            </TableCell>
                                            <TableCell className="max-w-xs truncate text-muted-foreground">
                                                {policy.steps_summary}
                                            </TableCell>
                                            <TableCell className="whitespace-nowrap text-muted-foreground">
                                                {formatDateRange(
                                                    policy.effective_from,
                                                    policy.effective_to,
                                                )}
                                            </TableCell>
                                            <TableCell className="tabular-nums">
                                                v{policy.version}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={
                                                        policy.is_active
                                                            ? 'default'
                                                            : 'outline'
                                                    }
                                                >
                                                    {policy.is_active
                                                        ? 'Activa'
                                                        : 'Inactiva'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex items-center justify-end gap-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={ApprovalPolicyController.edit.url(
                                                                policy.id,
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
                                                            setDeleteTarget(
                                                                policy,
                                                            )
                                                        }
                                                    >
                                                        <Trash2 className="size-4 text-destructive" />
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
