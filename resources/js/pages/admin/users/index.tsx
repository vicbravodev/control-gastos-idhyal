import { Head, Link } from '@inertiajs/react';
import { Pencil, Plus, UserRound } from 'lucide-react';

import StaffUserController from '@/actions/App/Http/Controllers/Admin/StaffUserController';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/idhyal';
import { TableToolbar } from '@/components/table-toolbar';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
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
import { cn } from '@/lib/utils';
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
    department: { id: number; name: string; code: string | null } | null;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Usuarios', href: StaffUserController.index.url() },
];

const ROLE_BADGE_CLASS: Record<string, string> = {
    super_admin: 'bg-[var(--destructive-bg)] text-[var(--destructive-fg)]',
    secretario_general:
        'bg-[var(--brand-blue-50)] text-[var(--brand-blue-700)]',
    contabilidad: 'bg-[var(--info-bg)] text-[var(--info-fg)]',
    coord_regional: 'bg-[var(--warning-bg)] text-[var(--warning-fg)]',
    coord_estatal: 'bg-[var(--warning-bg)] text-[var(--warning-fg)]',
};

function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((p) => p[0]?.toUpperCase() ?? '')
        .join('');
}

export default function AdminUsersIndex({
    users,
    filters,
    roles,
    departments,
}: {
    users: UserRow[];
    filters: Record<string, string>;
    roles: { value: string; label: string }[];
    departments: { value: string; label: string }[];
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Usuarios" />
            <div className="relative flex animate-fade-in flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    eyebrow="Administración"
                    title="Usuarios del sistema"
                    subtitle="Alta y edición de personal con rol organizacional, región y estado."
                    actions={
                        <Button asChild>
                            <Link href={StaffUserController.create.url()}>
                                <Plus />
                                Nuevo usuario
                            </Link>
                        </Button>
                    }
                />
                <div className="rounded-xl border border-border bg-card p-3.5">
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
                            {
                                key: 'department',
                                label: 'Departamento',
                                options: departments,
                                allLabel: 'Todos los departamentos',
                            },
                        ]}
                    />
                </div>
                <div className="overflow-hidden rounded-xl border border-border bg-card">
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
                                    <TableRow className="bg-[var(--card-soft)]">
                                        <TableHead>Usuario</TableHead>
                                        <TableHead>Rol</TableHead>
                                        <TableHead>Departamento</TableHead>
                                        <TableHead>Región</TableHead>
                                        <TableHead>Estado (geo)</TableHead>
                                        <TableHead className="w-[80px] text-right">
                                            Editar
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {users.map((u) => (
                                        <TableRow key={u.id}>
                                            <TableCell>
                                                <div className="flex items-center gap-3">
                                                    <Avatar className="size-9 shrink-0">
                                                        <AvatarFallback className="bg-[var(--brand-blue-100)] text-xs font-semibold text-[var(--brand-blue-700)]">
                                                            {initials(u.name)}
                                                        </AvatarFallback>
                                                    </Avatar>
                                                    <div className="min-w-0">
                                                        <div className="font-medium">
                                                            {u.name}
                                                        </div>
                                                        <div className="text-xs text-muted-foreground">
                                                            {u.email}
                                                        </div>
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                {u.role ? (
                                                    <Badge
                                                        variant="secondary"
                                                        className={cn(
                                                            'border-transparent font-semibold',
                                                            ROLE_BADGE_CLASS[
                                                                u.role.slug
                                                            ] ??
                                                                'bg-muted text-muted-foreground',
                                                        )}
                                                    >
                                                        {u.role.name}
                                                    </Badge>
                                                ) : (
                                                    <span className="text-muted-foreground">
                                                        —
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-sm text-muted-foreground">
                                                {u.department?.name ?? '—'}
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
                                                        <Pencil />
                                                    </Link>
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
