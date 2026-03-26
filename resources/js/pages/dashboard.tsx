import { Head, Link, usePage } from '@inertiajs/react';
import {
    CalendarDays,
    ClipboardList,
    Plus,
} from 'lucide-react';
import ExpenseRequestController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestController';
import VacationRequestController from '@/actions/App/Http/Controllers/VacationRequests/VacationRequestController';
import AppLogoIcon from '@/components/app-logo-icon';
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

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
];

export default function Dashboard() {
    const { auth } = usePage().props;
    const userName = (auth as { user?: { name?: string } })?.user?.name ?? 'Usuario';
    const firstName = userName.split(' ')[0];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex flex-col gap-4 p-4 animate-fade-in">
                <div className="flex items-start gap-4">
                    <div className="hidden rounded-xl bg-primary/10 p-3 sm:block">
                        <AppLogoIcon className="h-12 w-auto" />
                    </div>
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Bienvenido, {firstName}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Panel de control de gastos y solicitudes de IDHYAL.
                        </p>
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Card className="group relative overflow-hidden transition-shadow hover:shadow-md">
                        <div className="absolute top-0 right-0 h-24 w-24 translate-x-8 -translate-y-8 rounded-full bg-primary/5 transition-transform group-hover:scale-150" />
                        <CardHeader className="pb-3">
                            <div className="flex items-center gap-3">
                                <div className="rounded-lg bg-primary/10 p-2">
                                    <ClipboardList className="size-5 text-primary" />
                                </div>
                                <CardTitle className="text-base">
                                    Solicitudes de gasto
                                </CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <CardDescription className="mb-4">
                                Crea y da seguimiento a tus solicitudes de gasto.
                            </CardDescription>
                            <div className="flex flex-wrap gap-2">
                                <Button size="sm" asChild>
                                    <Link href={ExpenseRequestController.create.url()}>
                                        <Plus className="mr-1 size-3.5" />
                                        Nueva solicitud
                                    </Link>
                                </Button>
                                <Button size="sm" variant="outline" asChild>
                                    <Link href={ExpenseRequestController.index.url()}>
                                        Ver listado
                                    </Link>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="group relative overflow-hidden transition-shadow hover:shadow-md">
                        <div className="absolute top-0 right-0 h-24 w-24 translate-x-8 -translate-y-8 rounded-full bg-accent/10 transition-transform group-hover:scale-150" />
                        <CardHeader className="pb-3">
                            <div className="flex items-center gap-3">
                                <div className="rounded-lg bg-accent/15 p-2">
                                    <CalendarDays className="size-5 text-accent-foreground" />
                                </div>
                                <CardTitle className="text-base">
                                    Vacaciones
                                </CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <CardDescription className="mb-4">
                                Registra y consulta tus periodos vacacionales.
                            </CardDescription>
                            <div className="flex flex-wrap gap-2">
                                <Button size="sm" asChild>
                                    <Link href={VacationRequestController.create.url()}>
                                        <Plus className="mr-1 size-3.5" />
                                        Nueva solicitud
                                    </Link>
                                </Button>
                                <Button size="sm" variant="outline" asChild>
                                    <Link href={VacationRequestController.index.url()}>
                                        Ver listado
                                    </Link>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="group relative overflow-hidden transition-shadow hover:shadow-md sm:col-span-2 lg:col-span-1">
                        <div className="absolute top-0 right-0 h-24 w-24 translate-x-8 -translate-y-8 rounded-full bg-muted transition-transform group-hover:scale-150" />
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base">
                                Acceso rápido
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <CardDescription className="mb-4">
                                Navega directamente a las secciones del sistema.
                            </CardDescription>
                            <div className="grid grid-cols-2 gap-2">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    className="justify-start"
                                    asChild
                                >
                                    <Link href={ExpenseRequestController.index.url()}>
                                        Mis gastos
                                    </Link>
                                </Button>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    className="justify-start"
                                    asChild
                                >
                                    <Link href={VacationRequestController.index.url()}>
                                        Mis vacaciones
                                    </Link>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
