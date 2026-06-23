import { useForm } from '@inertiajs/react';
import { Bookmark } from 'lucide-react';
import { useEffect } from 'react';

import ReportTemplateController from '@/actions/App/Http/Controllers/Reports/ReportTemplateController';
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
import { Textarea } from '@/components/ui/textarea';

import type { FilterMap, GroupBy, ViewId } from './types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    filters: FilterMap;
    period: string;
    compare: boolean;
    view: ViewId;
    groupBy: GroupBy;
};

export function SaveViewDialog({
    open,
    onOpenChange,
    filters,
    period,
    compare,
    view,
    groupBy,
}: Props) {
    type FiltersPayload = FilterMap & { period: string; compare: boolean };
    type SaveForm = {
        name: string;
        description: string;
        icon: string;
        view: ViewId;
        group_by: GroupBy;
        filters: FiltersPayload;
        is_shared: boolean;
    };

    const form = useForm<SaveForm>({
        name: '',
        description: '',
        icon: 'bookmark',
        view,
        group_by: groupBy,
        filters: { ...filters, period, compare },
        is_shared: false,
    });

    useEffect(() => {
        if (open) {
            form.setData('view', view);
            form.setData('group_by', groupBy);
            form.setData('filters', { ...filters, period, compare });
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, view, groupBy, period, compare]);

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogTitle className="flex items-center gap-2">
                    <Bookmark className="size-4 text-[var(--brand-blue-600)]" />
                    Guardar vista
                </DialogTitle>
                <DialogDescription>
                    Guarda los filtros y la configuración actual como una plantilla reutilizable.
                </DialogDescription>
                <form
                    className="flex flex-col gap-3"
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post(ReportTemplateController.store.url(), {
                            preserveScroll: true,
                            onSuccess: () => {
                                onOpenChange(false);
                                form.reset('name', 'description');
                            },
                        });
                    }}
                >
                    <div className="grid gap-1.5">
                        <Label htmlFor="tpl-name">Nombre</Label>
                        <Input
                            id="tpl-name"
                            required
                            placeholder="Mi vista de cierre"
                            value={form.data.name}
                            onChange={(e) =>
                                form.setData('name', e.target.value)
                            }
                        />
                        <InputError message={form.errors.name} />
                    </div>
                    <div className="grid gap-1.5">
                        <Label htmlFor="tpl-desc">Descripción (opcional)</Label>
                        <Textarea
                            id="tpl-desc"
                            rows={2}
                            value={form.data.description}
                            onChange={(e) =>
                                form.setData('description', e.target.value)
                            }
                        />
                        <InputError message={form.errors.description} />
                    </div>
                    <label className="inline-flex cursor-pointer items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            className="size-4 rounded border-border accent-[var(--brand-blue-600)]"
                            checked={form.data.is_shared}
                            onChange={(e) =>
                                form.setData('is_shared', e.target.checked)
                            }
                        />
                        Compartir con el equipo de contabilidad
                    </label>
                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button variant="secondary" type="button">
                                Cancelar
                            </Button>
                        </DialogClose>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Guardando…' : 'Guardar vista'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
