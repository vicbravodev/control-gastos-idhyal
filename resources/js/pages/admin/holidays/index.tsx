import { Head, Link, router } from '@inertiajs/react';
import { CalendarOff, Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';
import HolidayController from '@/actions/App/Http/Controllers/Admin/HolidayController';
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

type HolidayRow = {
    id: number;
    date: string;
    name: string;
    description: string | null;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Días festivos', href: HolidayController.index.url() },
];

function formatLongDate(iso: string): string {
    try {
        return new Intl.DateTimeFormat('es-MX', {
            weekday: 'short',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        }).format(new Date(iso + 'T12:00:00'));
    } catch {
        return iso;
    }
}

export default function AdminHolidaysIndex({
    holidays,
    filters,
}: {
    holidays: HolidayRow[];
    filters: Record<string, string>;
}) {
    const [deleteTarget, setDeleteTarget] = useState<HolidayRow | null>(null);
    const [deleting, setDeleting] = useState(false);

    function handleConfirmDelete() {
        if (!deleteTarget) {
            return;
        }
        setDeleting(true);
        router.delete(HolidayController.destroy.url(deleteTarget.id), {
            preserveScroll: true,
            onFinish: () => {
                setDeleting(false);
                setDeleteTarget(null);
            },
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Días festivos" />
            <div className="relative flex animate-fade-in flex-col gap-4 overflow-hidden p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Días festivos"
                        description="Catálogo de festivos. Se descuentan automáticamente del cálculo de días de vacaciones."
                    />
                    <Button asChild>
                        <Link href={HolidayController.create.url()}>
                            Nuevo festivo
                        </Link>
                    </Button>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Listado</CardTitle>
                    </CardHeader>
                    <div className="px-6 pb-4">
                        <TableToolbar
                            currentUrl={HolidayController.index.url()}
                            filters={filters}
                            searchPlaceholder="Buscar por nombre…"
                        />
                    </div>
                    <CardContent>
                        {holidays.length === 0 ? (
                            <EmptyState
                                icon={CalendarOff}
                                title="Sin festivos registrados"
                                description="Agrega los días festivos del año para que se descuenten del cálculo de vacaciones."
                            />
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Fecha</TableHead>
                                        <TableHead>Nombre</TableHead>
                                        <TableHead>Descripción</TableHead>
                                        <TableHead className="w-[100px] text-right">
                                            Acciones
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {holidays.map((h) => (
                                        <TableRow key={h.id}>
                                            <TableCell className="font-medium">
                                                {formatLongDate(h.date)}
                                            </TableCell>
                                            <TableCell>{h.name}</TableCell>
                                            <TableCell className="text-sm text-muted-foreground">
                                                {h.description ?? '—'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={HolidayController.edit.url(
                                                                h.id,
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
                                                            setDeleteTarget(h)
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
                    if (!open) setDeleteTarget(null);
                }}
                title="Eliminar día festivo"
                description="¿Eliminar este festivo? El cálculo de vacaciones dejará de descontarlo."
                confirmLabel="Eliminar"
                variant="destructive"
                processing={deleting}
                onConfirm={handleConfirmDelete}
            />
        </AppLayout>
    );
}
