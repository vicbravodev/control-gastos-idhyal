import { Head, Link } from '@inertiajs/react';
import { UserRound } from 'lucide-react';
import StaffUserController from '@/actions/App/Http/Controllers/Admin/StaffUserController';
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

type UserRow = {
    id: number;
    name: string;
    email: string;
    username: string | null;
    phone: string | null;
    role: { id: number; slug: string; name: string } | null;
    region: { id: number; name: string; code: string | null } | null;
    state: { id: number; name: string; code: string | null } | null;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Usuarios', href: StaffUserController.index.url() },
];

export default function AdminUsersIndex({
    users,
    filters,
    roles,
}: {
    users: UserRow[];
    filters: Record<string, string>;
    roles: { value: string; label: string }[];
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Usuarios" />
            <div className="relative flex flex-col gap-4 overflow-hidden p-4 animate-fade-in">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Usuarios del sistema"
                        description="Alta y edición de personal con rol organizacional, región y estado."
                    />
                    <Button asChild>
                        <Link href={StaffUserController.create.url()}>
                            Nuevo usuario
                        </Link>
                    </Button>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Listado</CardTitle>
                    </CardHeader>
                    <div className="px-6 pb-4">
                        <TableToolbar
                            currentUrl={StaffUserController.index.url()}
                            filters={filters}
                            searchPlaceholder="Buscar por nombre o correo…"
                            filterDefinitions={[
                                {
                                    key: 'role',
                                    label: 'Rol',
                                    options: roles,
                                    allLabel: 'Todos los roles',
                                },
                            ]}
                        />
                    </div>
                    <CardContent>
                        {users.length === 0 ? (
                            <EmptyState
                                icon={UserRound}
                                title="Sin usuarios"
                                description="Cree el primer usuario desde el botón superior."
                            />
                        ) : (
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Nombre</TableHead>
                                            <TableHead>Correo</TableHead>
                                            <TableHead>Rol</TableHead>
                                            <TableHead>Región</TableHead>
                                            <TableHead>Estado</TableHead>
                                            <TableHead className="w-[80px] text-right">
                                                Editar
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {users.map((u) => (
                                            <TableRow key={u.id}>
                                                <TableCell className="font-medium">
                                                    {u.name}
                                                </TableCell>
                                                <TableCell className="text-muted-foreground text-sm">
                                                    {u.email}
                                                </TableCell>
                                                <TableCell className="text-sm">
                                                    {u.role?.name ?? '—'}
                                                </TableCell>
                                                <TableCell className="text-sm text-muted-foreground">
                                                    {u.region?.name ??
                                                        u.region?.code ??
                                                        '—'}
                                                </TableCell>
                                                <TableCell className="text-sm text-muted-foreground">
                                                    {u.state?.name ??
                                                        u.state?.code ??
                                                        '—'}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={StaffUserController.edit.url(
                                                                u.id,
                                                            )}
                                                        >
                                                            Editar
                                                        </Link>
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
