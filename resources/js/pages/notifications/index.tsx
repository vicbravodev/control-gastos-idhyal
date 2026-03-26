import { Form, Head, Link, usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import ExpenseRequestController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestController';
import NotificationInboxController from '@/actions/App/Http/Controllers/NotificationInboxController';
import VacationRequestController from '@/actions/App/Http/Controllers/VacationRequests/VacationRequestController';
import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import { PaginationNav } from '@/components/pagination-nav';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type Row = {
    id: string;
    read_at: string | null;
    created_at: string | null;
    title: string;
    body_lines: string[];
    expense_request_id: number | null;
    vacation_request_id: number | null;
};

type Paginator = {
    data: Row[];
    links: { url: string | null; label: string; active: boolean }[];
    last_page: number;
    current_page: number;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    {
        title: 'Notificaciones',
        href: NotificationInboxController.index.url(),
    },
];

function formatWhen(iso: string | null): string {
    if (!iso) {
        return '';
    }

    try {
        return new Intl.DateTimeFormat('es-MX', {
            dateStyle: 'medium',
            timeStyle: 'short',
        }).format(new Date(iso));
    } catch {
        return iso;
    }
}

export default function NotificationsIndex({
    notifications,
}: {
    notifications: Paginator;
}) {
    const page = usePage<{
        flash?: { status?: string | null };
    }>();
    const { flash } = page.props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Notificaciones" />
            <div className="flex flex-col gap-4 p-4 animate-fade-in">
                {flash?.status && (
                    <Alert>
                        <AlertDescription>{flash.status}</AlertDescription>
                    </Alert>
                )}
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Notificaciones"
                        description="Alertas del sistema relacionadas con solicitudes de gasto, vacaciones y tareas pendientes."
                    />
                    <Form
                        {...NotificationInboxController.markAllRead.form.post()}
                        options={{ preserveScroll: true }}
                    >
                        {({ processing }) => (
                            <Button
                                type="submit"
                                variant="outline"
                                disabled={
                                    processing || notifications.data.length === 0
                                }
                            >
                                {processing
                                    ? 'Marcando…'
                                    : 'Marcar todas como leídas'}
                            </Button>
                        )}
                    </Form>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Bandeja</CardTitle>
                        <CardDescription>
                            Las notificaciones más recientes aparecen primero.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {notifications.data.length === 0 ? (
                            <EmptyState
                                icon={Bell}
                                title="Sin notificaciones"
                                description="No tienes notificaciones."
                            />
                        ) : (
                            <>
                                <ul className="divide-y rounded-md border">
                                    {notifications.data.map((row) => (
                                        <li key={row.id}>
                                            <div className="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-start sm:justify-between">
                                                <div className="min-w-0 space-y-1">
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <span className="font-medium">
                                                            {row.title}
                                                        </span>
                                                        {row.read_at === null ? (
                                                            <Badge variant="secondary">
                                                                Sin leer
                                                            </Badge>
                                                        ) : null}
                                                    </div>
                                                    <p className="text-xs text-muted-foreground">
                                                        {formatWhen(
                                                            row.created_at,
                                                        )}
                                                    </p>
                                                    <div className="space-y-0.5 text-sm text-muted-foreground">
                                                        {row.body_lines.map(
                                                            (line, i) => (
                                                                <p key={i}>
                                                                    {line}
                                                                </p>
                                                            ),
                                                        )}
                                                    </div>
                                                </div>
                                                <div className="flex shrink-0 flex-col gap-2 sm:items-end">
                                                    {row.expense_request_id !==
                                                    null ? (
                                                        <Button asChild size="sm">
                                                            <Link
                                                                href={ExpenseRequestController.show.url(
                                                                    row.expense_request_id,
                                                                )}
                                                            >
                                                                Ver solicitud
                                                            </Link>
                                                        </Button>
                                                    ) : null}
                                                    {row.vacation_request_id !==
                                                    null ? (
                                                        <Button asChild size="sm">
                                                            <Link
                                                                href={VacationRequestController.show.url(
                                                                    row.vacation_request_id,
                                                                )}
                                                            >
                                                                Ver vacaciones
                                                            </Link>
                                                        </Button>
                                                    ) : null}
                                                    {row.read_at === null ? (
                                                        <Form
                                                            {...NotificationInboxController.markRead.form.post(
                                                                {
                                                                    id: row.id,
                                                                },
                                                            )}
                                                            options={{
                                                                preserveScroll:
                                                                    true,
                                                            }}
                                                        >
                                                            {({
                                                                processing,
                                                            }) => (
                                                                <Button
                                                                    type="submit"
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    disabled={
                                                                        processing
                                                                    }
                                                                >
                                                                    {processing
                                                                        ? '…'
                                                                        : 'Marcar leída'}
                                                                </Button>
                                                            )}
                                                        </Form>
                                                    ) : null}
                                                </div>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                                <PaginationNav
                                    links={notifications.links}
                                    currentPage={notifications.current_page}
                                    lastPage={notifications.last_page}
                                />
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
