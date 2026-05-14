import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    CalendarDays,
    ChevronRight,
    ClipboardList,
    Plus,
    Zap,
} from 'lucide-react';

import ExpenseRequestController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestController';
import VacationRequestController from '@/actions/App/Http/Controllers/VacationRequests/VacationRequestController';
import AppLogoIcon from '@/components/app-logo-icon';
import { HeroCard, QuickCard, StatCard } from '@/components/idhyal';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { formatCentsMx } from '@/lib/money';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
];

type ExpenseStats = {
    active: number;
    pending_approval: number;
    in_flight_amount_cents: number;
};

type RecentExpense = {
    id: number;
    folio: string;
    concept_label: string;
    status: string;
    requested_amount_cents: number;
};

type UserGender = 'male' | 'female' | 'prefer_not_to_say';

type DashboardPageProps = {
    expenseStats?: ExpenseStats;
    recentExpenses?: RecentExpense[];
    auth?: { user?: { name?: string; gender?: UserGender | null } };
};

const DEFAULT_STATS: ExpenseStats = {
    active: 0,
    pending_approval: 0,
    in_flight_amount_cents: 0,
};

function greetingFor(gender?: UserGender | null): string {
    if (gender === 'female') return 'Bienvenida';
    if (gender === 'male') return 'Bienvenido';
    return 'Bienvenido(a)';
}

export default function Dashboard() {
    const { auth, expenseStats, recentExpenses } =
        usePage<DashboardPageProps>().props;
    const userName = auth?.user?.name ?? 'Usuario';
    const firstName = userName.split(' ')[0];
    const greeting = greetingFor(auth?.user?.gender ?? null);
    const stats = expenseStats ?? DEFAULT_STATS;
    const recents = recentExpenses ?? [];
    const hasPending = stats.pending_approval > 0;
    const hasRecents = recents.length > 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex animate-fade-in flex-col gap-5 p-4 sm:p-6">
                <HeroCard
                    emblem={
                        <AppLogoIcon className="h-full w-auto object-contain" />
                    }
                    title={`${greeting}, ${firstName}`}
                    subtitle="Panel de control de gastos y solicitudes de IDHYAL."
                    actions={
                        <Button asChild>
                            <Link href={ExpenseRequestController.create.url()}>
                                <Plus />
                                Nueva solicitud
                            </Link>
                        </Button>
                    }
                />

                <div className="grid gap-4 lg:grid-cols-3">
                    <QuickCard
                        tone="blue"
                        icon={<ClipboardList className="size-5" aria-hidden />}
                        title="Solicitudes de gasto"
                        description="Crea y da seguimiento a tus solicitudes de gasto."
                        actions={
                            <>
                                <Button asChild size="sm">
                                    <Link
                                        href={ExpenseRequestController.create.url()}
                                    >
                                        <Plus />
                                        Nueva solicitud
                                    </Link>
                                </Button>
                                <Button asChild size="sm" variant="outline">
                                    <Link
                                        href={ExpenseRequestController.index.url()}
                                    >
                                        Ver listado
                                    </Link>
                                </Button>
                            </>
                        }
                    />

                    <QuickCard
                        tone="gold"
                        icon={<CalendarDays className="size-5" aria-hidden />}
                        title="Vacaciones"
                        description="Registra y consulta tus periodos vacacionales."
                        actions={
                            <>
                                <Button asChild size="sm">
                                    <Link
                                        href={VacationRequestController.create.url()}
                                    >
                                        <Plus />
                                        Nueva solicitud
                                    </Link>
                                </Button>
                                <Button asChild size="sm" variant="outline">
                                    <Link
                                        href={VacationRequestController.index.url()}
                                    >
                                        Ver listado
                                    </Link>
                                </Button>
                            </>
                        }
                    />

                    <QuickCard
                        tone="muted"
                        icon={<Zap className="size-5" aria-hidden />}
                        title="Acceso rápido"
                        description="Navega directamente a las secciones del sistema."
                        actions={
                            <>
                                <Button asChild size="sm" variant="outline">
                                    <Link
                                        href={ExpenseRequestController.index.url()}
                                    >
                                        Mis gastos
                                    </Link>
                                </Button>
                                <Button asChild size="sm" variant="outline">
                                    <Link
                                        href={VacationRequestController.index.url()}
                                    >
                                        Mis vacaciones
                                    </Link>
                                </Button>
                            </>
                        }
                    />
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <StatCard
                        label="Solicitudes activas"
                        value={stats.active}
                        hint="En curso ahora mismo"
                        footer={
                            <Button
                                asChild
                                variant="link"
                                size="sm"
                                className="h-auto p-0 text-[var(--brand-blue-700)]"
                            >
                                <Link
                                    href={ExpenseRequestController.index.url()}
                                >
                                    Ver listado <ArrowRight />
                                </Link>
                            </Button>
                        }
                    />
                    <StatCard
                        label="Pendientes de tu aprobación"
                        value={stats.pending_approval}
                        delta={hasPending ? 'Requieren atención' : undefined}
                        deltaTone={hasPending ? 'negative' : 'neutral'}
                    />
                    <StatCard
                        label="Monto en curso"
                        value={formatCentsMx(stats.in_flight_amount_cents)}
                        hint="Solicitudes en proceso"
                    />
                </div>

                <section className="overflow-hidden rounded-xl border border-border bg-card">
                    <header className="flex items-center gap-3 border-b border-border px-5 py-3.5">
                        <ClipboardList
                            className="size-4 text-[var(--brand-blue-600)]"
                            aria-hidden
                        />
                        <h2 className="text-sm font-semibold">
                            Actividad reciente
                        </h2>
                        <Button
                            asChild
                            variant="ghost"
                            size="sm"
                            className="ml-auto"
                        >
                            <Link href={ExpenseRequestController.index.url()}>
                                Ver todo
                            </Link>
                        </Button>
                    </header>
                    {hasRecents ? (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-[var(--card-soft)] text-[11px] tracking-wider text-muted-foreground uppercase">
                                    <tr>
                                        <th className="px-4 py-2.5 text-left font-semibold">
                                            Folio
                                        </th>
                                        <th className="px-4 py-2.5 text-left font-semibold">
                                            Concepto
                                        </th>
                                        <th className="px-4 py-2.5 text-right font-semibold">
                                            Monto
                                        </th>
                                        <th className="px-4 py-2.5 text-left font-semibold">
                                            Estado
                                        </th>
                                        <th className="px-4 py-2.5" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {recents.map((row) => (
                                        <tr
                                            key={row.id}
                                            className="border-t border-border transition-colors hover:bg-[var(--card-soft)]"
                                        >
                                            <td className="px-4 py-3">
                                                <Link
                                                    href={ExpenseRequestController.show.url(
                                                        row.id,
                                                    )}
                                                    className="t-folio text-[var(--brand-blue-700)]"
                                                >
                                                    {row.folio}
                                                </Link>
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {row.concept_label}
                                            </td>
                                            <td className="t-num px-4 py-3 text-right font-semibold">
                                                {formatCentsMx(
                                                    row.requested_amount_cents,
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                <StatusBadge
                                                    status={row.status}
                                                />
                                            </td>
                                            <td className="px-4 py-3 text-right text-muted-foreground">
                                                <ChevronRight
                                                    className="size-4"
                                                    aria-hidden
                                                />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <div className="px-5 py-10 text-center text-sm text-muted-foreground">
                            <p className="font-medium text-foreground">
                                Aún no has creado solicitudes
                            </p>
                            <p className="mt-1">
                                Cuando registres tu primera, aparecerá aquí.
                            </p>
                        </div>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
