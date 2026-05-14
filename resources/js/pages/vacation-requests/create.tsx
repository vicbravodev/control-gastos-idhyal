import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft, Send } from 'lucide-react';
import { useState } from 'react';

import VacationRequestController from '@/actions/App/Http/Controllers/VacationRequests/VacationRequestController';
import { PageHeader } from '@/components/idhyal';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { Label } from '@/components/ui/label';
import { VacationBalanceCard } from '@/components/vacation-balance-card';
import type { VacationBalancePayload } from '@/components/vacation-balance-card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    {
        title: 'Vacaciones',
        href: VacationRequestController.index.url(),
    },
    {
        title: 'Nueva',
        href: VacationRequestController.create.url(),
    },
];

export default function VacationRequestsCreate({
    vacationBalance,
}: {
    vacationBalance: VacationBalancePayload | null;
}) {
    const [startsOn, setStartsOn] = useState('');
    const [endsOn, setEndsOn] = useState('');

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva solicitud de vacaciones" />
            <div className="mx-auto flex w-full max-w-2xl animate-fade-in flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    eyebrow="Vacaciones"
                    title="Nueva solicitud de vacaciones"
                    subtitle="Indica el periodo solicitado. El sistema contará los días hábiles (lunes a viernes) al enviar."
                    actions={
                        <Button variant="outline" asChild>
                            <Link href={VacationRequestController.index.url()}>
                                <ArrowLeft />
                                Cancelar
                            </Link>
                        </Button>
                    }
                />
                <VacationBalanceCard balance={vacationBalance} />
                <Form
                    {...VacationRequestController.store.form()}
                    className="flex flex-col gap-5"
                    options={{ preserveScroll: true }}
                >
                    {({ processing, errors }) => (
                        <>
                            <section className="rounded-xl border border-border bg-card p-5 sm:p-6">
                                <header className="mb-4">
                                    <h2 className="text-sm font-semibold">
                                        Periodo
                                    </h2>
                                </header>
                                <InputError message={errors.approval_policy} />
                                <InputError message={errors.hire_date} />
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="starts_on">
                                            Fecha de inicio
                                        </Label>
                                        <input
                                            type="hidden"
                                            name="starts_on"
                                            value={startsOn}
                                        />
                                        <DatePicker
                                            id="starts_on"
                                            value={startsOn}
                                            onChange={setStartsOn}
                                            disablePast
                                        />
                                        <InputError
                                            message={errors.starts_on}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="ends_on">
                                            Fecha de fin
                                        </Label>
                                        <input
                                            type="hidden"
                                            name="ends_on"
                                            value={endsOn}
                                        />
                                        <DatePicker
                                            id="ends_on"
                                            value={endsOn}
                                            onChange={setEndsOn}
                                            disablePast
                                        />
                                        <InputError message={errors.ends_on} />
                                    </div>
                                </div>
                            </section>
                            <div className="flex flex-wrap gap-2">
                                <Button type="submit" disabled={processing}>
                                    <Send />
                                    {processing
                                        ? 'Enviando…'
                                        : 'Enviar a aprobación'}
                                </Button>
                                <Button variant="outline" type="button" asChild>
                                    <Link
                                        href={VacationRequestController.index.url()}
                                    >
                                        Cancelar
                                    </Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
