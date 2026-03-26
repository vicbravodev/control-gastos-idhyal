import { CalendarClock, Info, Palmtree, Sparkles } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Card } from '@/components/ui/card';

export type VacationBalancePayload = {
    has_hire_date: boolean;
    service_years: number | null;
    calendar_year: number;
    rule: {
        id: number;
        name: string;
        code: string;
        max_days_per_request: number | null;
    } | null;
    days_allocated: number;
    days_consumed: number;
    days_remaining: number;
    pending_first_year: boolean;
    first_anniversary_on: string | null;
    days_until_anniversary: number | null;
};

function formatLongDate(iso: string): string {
    try {
        return new Intl.DateTimeFormat('es-MX', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        }).format(new Date(iso + 'T12:00:00'));
    } catch {
        return iso;
    }
}

export function VacationBalanceCard({
    balance,
}: {
    balance: VacationBalancePayload | null;
}) {
    if (balance === null) {
        return null;
    }

    if (!balance.has_hire_date) {
        return (
            <Alert>
                <Info className="size-4" />
                <AlertTitle>Sin fecha de ingreso</AlertTitle>
                <AlertDescription>
                    Tu perfil no tiene fecha de ingreso registrada. Pide a un
                    administrador que la actualice en el directorio de personal
                    para calcular tus días.
                </AlertDescription>
            </Alert>
        );
    }

    if (balance.pending_first_year) {
        return (
            <Alert>
                <CalendarClock className="size-4" />
                <AlertTitle>Próximo a tu primer año</AlertTitle>
                <AlertDescription>
                    {balance.first_anniversary_on && (
                        <p>
                            Tu primer aniversario laboral será el{' '}
                            <span className="font-medium text-foreground">
                                {formatLongDate(balance.first_anniversary_on)}
                            </span>
                            .
                        </p>
                    )}
                    {balance.days_until_anniversary !== null && (
                        <p className="font-medium text-[var(--color-brand-blue)]">
                            Faltan {balance.days_until_anniversary}{' '}
                            {balance.days_until_anniversary === 1
                                ? 'día'
                                : 'días'}{' '}
                            para ese hito.
                        </p>
                    )}
                </AlertDescription>
            </Alert>
        );
    }

    if (!balance.rule) {
        return (
            <Alert>
                <Info className="size-4" />
                <AlertTitle>Sin regla de vacaciones</AlertTitle>
                <AlertDescription>
                    No hay una regla de vacaciones configurada para tu
                    antigüedad. Contacta a administración.
                </AlertDescription>
            </Alert>
        );
    }

    return (
        <Card className="animate-slide-up relative overflow-hidden border border-[var(--color-brand-blue)]/25 bg-gradient-to-br from-[var(--color-brand-blue)]/[0.07] via-card to-[var(--color-brand-gold)]/[0.06] shadow-[0_12px_40px_-18px_var(--color-brand-blue)]">
            <div
                className="pointer-events-none absolute -right-8 -top-10 size-40 rounded-full bg-[var(--color-brand-gold)]/15 blur-2xl"
                aria-hidden
            />
            <div
                className="pointer-events-none absolute -bottom-12 left-1/4 size-36 rounded-full bg-[var(--color-brand-blue)]/10 blur-2xl"
                aria-hidden
            />
            <div className="relative flex flex-col gap-4 p-5 sm:flex-row sm:items-stretch sm:justify-between sm:gap-8">
                <div className="flex min-w-0 flex-1 flex-col gap-2">
                    <div className="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.14em] text-muted-foreground">
                        <Sparkles className="size-3.5 text-[var(--color-brand-gold)]" />
                        Tu saldo de vacaciones
                    </div>
                    <div className="space-y-3">
                        <div className="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                            <span
                                className="text-5xl font-semibold tabular-nums tracking-tight text-foreground"
                                aria-label={`Días restantes: ${balance.days_remaining}`}
                            >
                                {balance.days_remaining}
                            </span>
                            <span className="text-sm text-muted-foreground">
                                días hábiles disponibles
                            </span>
                        </div>
                        <p className="text-sm text-muted-foreground">
                            Año {balance.calendar_year} ·{' '}
                            <span className="font-medium text-foreground">
                                {balance.rule.name}
                            </span>
                            {balance.service_years !== null && (
                                <>
                                    {' '}
                                    · Antigüedad aprox.{' '}
                                    {balance.service_years} años
                                </>
                            )}
                        </p>
                        <dl className="grid max-w-md grid-cols-2 gap-x-4 gap-y-1 text-sm">
                            <dt className="text-muted-foreground">
                                Asignados
                            </dt>
                            <dd className="text-right font-medium tabular-nums">
                                {balance.days_allocated}
                            </dd>
                            <dt className="text-muted-foreground">
                                En uso / en trámite
                            </dt>
                            <dd className="text-right font-medium tabular-nums">
                                {balance.days_consumed}
                            </dd>
                        </dl>
                    </div>
                </div>
                <div className="flex shrink-0 items-center justify-center sm:w-28">
                    <div className="flex size-20 items-center justify-center rounded-2xl border border-[var(--color-brand-gold)]/35 bg-[var(--color-brand-gold)]/10 shadow-inner sm:size-24">
                        <Palmtree
                            className="size-10 text-[var(--color-brand-blue)] sm:size-11"
                            strokeWidth={1.25}
                        />
                    </div>
                </div>
            </div>
        </Card>
    );
}
