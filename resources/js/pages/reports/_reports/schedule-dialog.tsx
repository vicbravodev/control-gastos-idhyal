import { useForm } from '@inertiajs/react';
import { Clock, Plus, X } from 'lucide-react';
import { useEffect, useState } from 'react';

import ReportScheduleController from '@/actions/App/Http/Controllers/Reports/ReportScheduleController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

import type { Template } from './types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    templates: Template[];
    defaultTemplateId: number | null;
};

const WEEKDAYS = [
    { value: '1', label: 'Lunes' },
    { value: '2', label: 'Martes' },
    { value: '3', label: 'Miércoles' },
    { value: '4', label: 'Jueves' },
    { value: '5', label: 'Viernes' },
    { value: '6', label: 'Sábado' },
    { value: '0', label: 'Domingo' },
];

export function ScheduleDialog({
    open,
    onOpenChange,
    templates,
    defaultTemplateId,
}: Props) {
    const form = useForm<{
        template_id: string;
        cadence: 'daily' | 'weekly' | 'monthly';
        day_of_week: string;
        day_of_month: string;
        time_of_day: string;
        format: 'pdf' | 'csv';
        recipients: string[];
    }>({
        template_id: defaultTemplateId ? String(defaultTemplateId) : '',
        cadence: 'daily',
        day_of_week: '1',
        day_of_month: '1',
        time_of_day: '07:00',
        format: 'pdf',
        recipients: [''],
    });

    const [recipientDraft, setRecipientDraft] = useState('');

    useEffect(() => {
        if (open) {
            form.setData(
                'template_id',
                defaultTemplateId ? String(defaultTemplateId) : '',
            );
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, defaultTemplateId]);

    const removeRecipient = (index: number) => {
        form.setData(
            'recipients',
            form.data.recipients.filter((_, i) => i !== index),
        );
    };

    const addRecipient = () => {
        const v = recipientDraft.trim();
        if (!v) return;
        form.setData('recipients', [
            ...form.data.recipients.filter((r) => r !== ''),
            v,
        ]);
        setRecipientDraft('');
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogTitle className="flex items-center gap-2">
                    <Clock className="size-4 text-[var(--brand-blue-600)]" />
                    Programar reporte
                </DialogTitle>
                <DialogDescription>
                    Recibe este reporte por correo en una cadencia recurrente.
                </DialogDescription>
                <form
                    className="flex flex-col gap-3"
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.transform((data) => ({
                            ...data,
                            template_id: Number(data.template_id),
                            day_of_week:
                                data.cadence === 'weekly'
                                    ? Number(data.day_of_week)
                                    : null,
                            day_of_month:
                                data.cadence === 'monthly'
                                    ? Number(data.day_of_month)
                                    : null,
                            recipients: data.recipients.filter((r) => r),
                        }));
                        form.post(ReportScheduleController.store.url(), {
                            preserveScroll: true,
                            onSuccess: () => {
                                onOpenChange(false);
                                form.reset();
                            },
                        });
                    }}
                >
                    <div className="grid gap-1.5">
                        <Label htmlFor="sched-tpl">Plantilla</Label>
                        <Select
                            value={form.data.template_id}
                            onValueChange={(v) => form.setData('template_id', v)}
                        >
                            <SelectTrigger id="sched-tpl">
                                <SelectValue placeholder="Selecciona una plantilla" />
                            </SelectTrigger>
                            <SelectContent>
                                {templates.map((t) => (
                                    <SelectItem key={t.id} value={String(t.id)}>
                                        {t.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.template_id} />
                    </div>

                    <div className="grid gap-1.5 sm:grid-cols-2">
                        <div className="grid gap-1.5">
                            <Label htmlFor="sched-cad">Cadencia</Label>
                            <Select
                                value={form.data.cadence}
                                onValueChange={(v) =>
                                    form.setData(
                                        'cadence',
                                        v as 'daily' | 'weekly' | 'monthly',
                                    )
                                }
                            >
                                <SelectTrigger id="sched-cad">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="daily">Diaria</SelectItem>
                                    <SelectItem value="weekly">Semanal</SelectItem>
                                    <SelectItem value="monthly">Mensual</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="grid gap-1.5">
                            <Label htmlFor="sched-time">Hora (CDMX)</Label>
                            <Input
                                id="sched-time"
                                type="time"
                                value={form.data.time_of_day}
                                onChange={(e) =>
                                    form.setData('time_of_day', e.target.value)
                                }
                            />
                        </div>
                    </div>

                    {form.data.cadence === 'weekly' && (
                        <div className="grid gap-1.5">
                            <Label htmlFor="sched-dow">Día de la semana</Label>
                            <Select
                                value={form.data.day_of_week}
                                onValueChange={(v) =>
                                    form.setData('day_of_week', v)
                                }
                            >
                                <SelectTrigger id="sched-dow">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {WEEKDAYS.map((d) => (
                                        <SelectItem key={d.value} value={d.value}>
                                            {d.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    )}
                    {form.data.cadence === 'monthly' && (
                        <div className="grid gap-1.5">
                            <Label htmlFor="sched-dom">Día del mes</Label>
                            <Input
                                id="sched-dom"
                                type="number"
                                min={1}
                                max={31}
                                value={form.data.day_of_month}
                                onChange={(e) =>
                                    form.setData('day_of_month', e.target.value)
                                }
                            />
                        </div>
                    )}

                    <div className="grid gap-1.5">
                        <Label>Formato</Label>
                        <div className="flex gap-2">
                            {(['pdf', 'csv'] as const).map((f) => (
                                <label
                                    key={f}
                                    className={`inline-flex cursor-pointer items-center gap-2 rounded-md border px-3 py-1.5 text-sm ${
                                        form.data.format === f
                                            ? 'border-[var(--brand-blue-300)] bg-[var(--brand-blue-50)]'
                                            : 'border-border'
                                    }`}
                                >
                                    <input
                                        type="radio"
                                        className="size-3.5 accent-[var(--brand-blue-600)]"
                                        checked={form.data.format === f}
                                        onChange={() =>
                                            form.setData('format', f)
                                        }
                                    />
                                    {f.toUpperCase()}
                                </label>
                            ))}
                        </div>
                    </div>

                    <div className="grid gap-1.5">
                        <Label>Destinatarios</Label>
                        <div className="flex flex-wrap gap-1.5">
                            {form.data.recipients
                                .filter((r) => r)
                                .map((r, idx) => (
                                    <span
                                        key={`${r}-${idx}`}
                                        className="inline-flex items-center gap-1.5 rounded-full bg-[var(--brand-blue-50)] py-1 pl-3 pr-1 text-xs text-[var(--brand-blue-800)]"
                                    >
                                        {r}
                                        <button
                                            type="button"
                                            className="inline-flex size-4 items-center justify-center rounded-full hover:bg-[var(--brand-blue-200)]"
                                            onClick={() => removeRecipient(idx)}
                                        >
                                            <X className="size-3" />
                                        </button>
                                    </span>
                                ))}
                        </div>
                        <div className="flex gap-2">
                            <Input
                                type="email"
                                placeholder="correo@idhyal.com"
                                value={recipientDraft}
                                onChange={(e) => setRecipientDraft(e.target.value)}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter') {
                                        e.preventDefault();
                                        addRecipient();
                                    }
                                }}
                            />
                            <Button
                                type="button"
                                variant="outline"
                                onClick={addRecipient}
                            >
                                <Plus />
                                Agregar
                            </Button>
                        </div>
                        <InputError message={form.errors.recipients} />
                    </div>

                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button variant="secondary" type="button">
                                Cancelar
                            </Button>
                        </DialogClose>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Guardando…' : 'Programar'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
