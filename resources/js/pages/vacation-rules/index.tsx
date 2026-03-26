import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Palmtree, Trash2 } from 'lucide-react';
import { useState } from 'react';
import VacationRuleController from '@/actions/App/Http/Controllers/VacationRules/VacationRuleController';
import ConfirmationDialog from '@/components/confirmation-dialog';
import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
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

type RuleRow = {
    id: number;
    code: string;
    name: string;
    min_years_service: number;
    max_years_service: number | null;
    days_granted_per_year: number;
    max_days_per_request: number | null;
    sort_order: number;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    {
        title: 'Reglas de vacaciones',
        href: VacationRuleController.index.url(),
    },
];

function formatYearsRange(min: number, max: number | null): string {
    if (max === null) {
        return `${min}+ años`;
    }

    return `${min} – ${max} años`;
}

export default function VacationRulesIndex({ rules }: { rules: RuleRow[] }) {
    const [deleteTarget, setDeleteTarget] = useState<RuleRow | null>(null);
    const [deleting, setDeleting] = useState(false);

    function handleConfirmDelete() {
        if (!deleteTarget) {
            return;
        }

        setDeleting(true);
        router.delete(VacationRuleController.destroy.url(deleteTarget.id), {
            preserveScroll: true,
            onFinish: () => {
                setDeleting(false);
                setDeleteTarget(null);
            },
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reglas de vacaciones" />
            <div className="flex animate-fade-in flex-col gap-4 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Reglas de vacaciones"
                        description="Tramos de antigüedad, días anuales y límites por solicitud."
                    />
                    <Button asChild>
                        <Link href={VacationRuleController.create.url()}>
                            Nueva regla
                        </Link>
                    </Button>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Listado</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {rules.length === 0 ? (
                            <EmptyState
                                icon={Palmtree}
                                title="Sin reglas"
                                description="Crea la primera regla para habilitar cálculo de saldos."
                                action={
                                    <Button asChild size="sm">
                                        <Link
                                            href={VacationRuleController.create.url()}
                                        >
                                            Nueva regla
                                        </Link>
                                    </Button>
                                }
                            />
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Orden</TableHead>
                                        <TableHead>Código</TableHead>
                                        <TableHead>Nombre</TableHead>
                                        <TableHead>Antigüedad</TableHead>
                                        <TableHead className="text-right">
                                            Días / año
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Máx. / sol.
                                        </TableHead>
                                        <TableHead className="w-[120px]" />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {rules.map((row) => (
                                        <TableRow key={row.id}>
                                            <TableCell className="tabular-nums">
                                                {row.sort_order}
                                            </TableCell>
                                            <TableCell className="font-mono text-sm">
                                                {row.code}
                                            </TableCell>
                                            <TableCell>{row.name}</TableCell>
                                            <TableCell className="text-sm text-muted-foreground">
                                                {formatYearsRange(
                                                    row.min_years_service,
                                                    row.max_years_service,
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {row.days_granted_per_year}
                                            </TableCell>
                                            <TableCell className="text-right text-muted-foreground tabular-nums">
                                                {row.max_days_per_request ??
                                                    '—'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={VacationRuleController.edit.url(
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
                                                        type="button"
                                                        onClick={() =>
                                                            setDeleteTarget(row)
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
                title="Eliminar regla"
                description="¿Eliminar esta regla? Las asignaciones ya guardadas no se recalculan solas."
                confirmLabel="Eliminar"
                variant="destructive"
                processing={deleting}
                onConfirm={handleConfirmDelete}
            />
        </AppLayout>
    );
}
