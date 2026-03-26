import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import VacationRequestController from '@/actions/App/Http/Controllers/VacationRequests/VacationRequestController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import {
    VacationBalanceCard,
    type VacationBalancePayload,
} from '@/components/vacation-balance-card';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { Label } from '@/components/ui/label';
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
            <div className="mx-auto flex w-full max-w-2xl flex-col gap-4 p-4">
                <Heading
                    title="Nueva solicitud de vacaciones"
                    description="Indica el periodo solicitado. El sistema contará los días hábiles (lunes a viernes) al enviar."
                />
                <VacationBalanceCard balance={vacationBalance} />
                <Form
                    {...VacationRequestController.store.form()}
                    className="flex flex-col gap-6"
                    options={{ preserveScroll: true }}
                >
                    {({ processing, errors }) => (
                        <>
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
                                    />
                                    <InputError message={errors.starts_on} />
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
                                    />
                                    <InputError message={errors.ends_on} />
                                </div>
                            </div>
                            <div className="flex flex-wrap gap-3">
                                <Button type="submit" disabled={processing}>
                                    {processing
                                        ? 'Enviando…'
                                        : 'Enviar a aprobación'}
                                </Button>
                                <Button variant="outline" type="button" asChild>
                                    <Link href={VacationRequestController.index.url()}>
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
