import { Form, Link, router, usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { useCallback, useState } from 'react';
import ExpenseRequestController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestController';
import NotificationInboxController from '@/actions/App/Http/Controllers/NotificationInboxController';
import VacationRequestController from '@/actions/App/Http/Controllers/VacationRequests/VacationRequestController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import type { Auth } from '@/types';

type Row = {
    id: string;
    read_at: string | null;
    created_at: string | null;
    title: string;
    body_lines: string[];
    expense_request_id: number | null;
    vacation_request_id: number | null;
};

function formatWhen(iso: string | null): string {
    if (!iso) {
        return '';
    }

    try {
        return new Intl.DateTimeFormat('es-MX', {
            dateStyle: 'short',
            timeStyle: 'short',
        }).format(new Date(iso));
    } catch {
        return iso;
    }
}

export function NotificationHeaderWidget() {
    const page = usePage<{ auth: Auth }>();
    const { auth } = page.props;
    const unread = auth.user?.unread_notifications_count ?? 0;

    const [open, setOpen] = useState(false);
    const [items, setItems] = useState<Row[]>([]);
    const [loading, setLoading] = useState(false);

    const loadPreview = useCallback(() => {
        setLoading(true);
        fetch(NotificationInboxController.preview.url(), {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then((r) => r.json())
            .then((body: { data: Row[] }) => {
                setItems(body.data ?? []);
            })
            .catch(() => {
                setItems([]);
            })
            .finally(() => {
                setLoading(false);
            });
    }, []);

    const handleOpenChange = (next: boolean) => {
        setOpen(next);
        if (next) {
            loadPreview();
        }
    };

    const reloadShell = () => {
        router.reload({ preserveScroll: true });
    };

    return (
        <DropdownMenu open={open} onOpenChange={handleOpenChange}>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="relative h-9 w-9 shrink-0"
                    aria-label="Notificaciones"
                >
                    <Bell className="size-5" />
                    {unread > 0 ? (
                        <Badge
                            variant="destructive"
                            className="absolute -top-0.5 -right-0.5 flex h-5 min-w-5 items-center justify-center rounded-full px-1 text-[10px] tabular-nums"
                        >
                            {unread > 99 ? '99+' : unread}
                        </Badge>
                    ) : null}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent
                align="end"
                className="flex w-80 max-w-[calc(100vw-2rem)] flex-col overflow-hidden p-0"
                sideOffset={8}
            >
                <div className="border-b px-3 py-2">
                    <p className="text-sm font-semibold">Notificaciones</p>
                    <p className="text-muted-foreground text-xs">
                        Últimas alertas del sistema
                    </p>
                </div>
                <div className="max-h-72 overflow-y-auto">
                    {loading ? (
                        <p className="text-muted-foreground px-3 py-6 text-center text-sm">
                            Cargando…
                        </p>
                    ) : items.length === 0 ? (
                        <p className="text-muted-foreground px-3 py-6 text-center text-sm">
                            No tienes notificaciones.
                        </p>
                    ) : (
                        <ul className="divide-y">
                            {items.map((row) => (
                                <li
                                    key={row.id}
                                    className={cn(
                                        'px-3 py-2.5',
                                        row.read_at === null &&
                                            'bg-muted/40',
                                    )}
                                >
                                    <div className="flex flex-col gap-1.5">
                                        <div className="flex items-start justify-between gap-2">
                                            <span className="text-sm leading-tight font-medium">
                                                {row.title}
                                            </span>
                                            {row.read_at === null ? (
                                                <Badge
                                                    variant="secondary"
                                                    className="shrink-0 text-[10px]"
                                                >
                                                    Nueva
                                                </Badge>
                                            ) : null}
                                        </div>
                                        <p className="text-muted-foreground text-[11px]">
                                            {formatWhen(row.created_at)}
                                        </p>
                                        <div className="text-muted-foreground line-clamp-2 text-xs">
                                            {row.body_lines.join(' · ')}
                                        </div>
                                        <div className="flex flex-wrap gap-1.5 pt-0.5">
                                            {row.expense_request_id !==
                                            null ? (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    className="h-7 text-xs"
                                                    asChild
                                                >
                                                    <Link
                                                        href={ExpenseRequestController.show.url(
                                                            row.expense_request_id,
                                                        )}
                                                    >
                                                        Ver gasto
                                                    </Link>
                                                </Button>
                                            ) : null}
                                            {row.vacation_request_id !==
                                            null ? (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    className="h-7 text-xs"
                                                    asChild
                                                >
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
                                                        { id: row.id },
                                                    )}
                                                    options={{
                                                        preserveScroll: true,
                                                        onSuccess:
                                                            reloadShell,
                                                    }}
                                                >
                                                    {({ processing }) => (
                                                        <Button
                                                            type="submit"
                                                            variant="ghost"
                                                            size="sm"
                                                            className="h-7 text-xs"
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
                    )}
                </div>
                <div className="bg-muted/30 flex flex-col gap-2 border-t px-2 py-2">
                    <Button variant="outline" size="sm" className="w-full" asChild>
                        <Link
                            href={NotificationInboxController.index.url()}
                            onClick={() => setOpen(false)}
                        >
                            Ver todas
                        </Link>
                    </Button>
                    <Form
                        {...NotificationInboxController.markAllRead.form.post()}
                        options={{
                            preserveScroll: true,
                            onSuccess: () => {
                                setOpen(false);
                                reloadShell();
                            },
                        }}
                    >
                        {({ processing }) => (
                            <Button
                                type="submit"
                                variant="secondary"
                                size="sm"
                                className="w-full"
                                disabled={processing || unread === 0}
                            >
                                {processing
                                    ? 'Marcando…'
                                    : 'Marcar todas como leídas'}
                            </Button>
                        )}
                    </Form>
                </div>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
