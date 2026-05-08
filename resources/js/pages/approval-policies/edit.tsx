import { Head, Link, router, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import type { FormEvent } from 'react';
import ApprovalPolicyController from '@/actions/App/Http/Controllers/ApprovalPolicies/ApprovalPolicyController';
import { ApproverPicker } from '@/components/approval-policies/approver-picker';
import ConfirmationDialog from '@/components/confirmation-dialog';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { buildChainPreview } from '@/lib/approval-chain-preview';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type RoleOption = { id: number; name: string };
type DocumentTypeOption = { value: string; label: string };
type StepModeOption = { value: string; label: string };

type StepData = {
    approver_type: 'role' | 'department' | 'user';
    approver_id: string;
    approver_label: string;
    step_mode: string;
};

type AppliesToType = 'all' | 'role' | 'department' | 'user';

type PolicyData = {
    id: number;
    document_type: string;
    name: string;
    version: number;
    applies_to_type: string | null;
    applies_to_id: number | null;
    applies_to_label: string | null;
    effective_from: string | null;
    effective_to: string | null;
    is_active: boolean;
    steps: {
        approver_type: string;
        approver_id: number;
        approver_label: string;
        step_mode: string;
    }[];
};

export default function ApprovalPoliciesEdit({
    policy,
    roles,
    departments,
    documentTypes,
    stepModes,
    userSearchUrl,
    can,
}: {
    policy: PolicyData;
    roles: RoleOption[];
    departments: RoleOption[];
    documentTypes: DocumentTypeOption[];
    stepModes: StepModeOption[];
    userSearchUrl: string;
    can: { delete: boolean };
}) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard() },
        {
            title: 'Políticas de aprobación',
            href: ApprovalPolicyController.index.url(),
        },
        {
            title: policy.name,
            href: ApprovalPolicyController.edit.url(policy.id),
        },
    ];

    const initialKind: AppliesToType =
        policy.applies_to_type === null
            ? 'all'
            : (policy.applies_to_type as AppliesToType);

    const { data, setData, transform, patch, processing, errors } = useForm({
        document_type: policy.document_type,
        name: policy.name,
        version: policy.version,
        applies_to_kind: initialKind,
        applies_to_id:
            policy.applies_to_id !== null ? String(policy.applies_to_id) : '',
        applies_to_user_label: policy.applies_to_label ?? '',
        effective_from: policy.effective_from ?? '',
        effective_to: policy.effective_to ?? '',
        is_active: policy.is_active,
        steps: policy.steps.map((s) => ({
            approver_type: s.approver_type as 'role' | 'department' | 'user',
            approver_id: String(s.approver_id),
            approver_label: s.approver_label,
            step_mode: s.step_mode,
        })) as StepData[],
    });

    transform((d) => ({
        document_type: d.document_type,
        name: d.name,
        version: d.version,
        applies_to_type: d.applies_to_kind === 'all' ? null : d.applies_to_kind,
        applies_to_id: d.applies_to_kind === 'all' ? null : d.applies_to_id,
        effective_from: d.effective_from,
        effective_to: d.effective_to,
        is_active: d.is_active,
        steps: d.steps.map((s) => ({
            approver_type: s.approver_type,
            approver_id: s.approver_id,
            step_mode: s.step_mode,
        })),
    }));

    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [deleting, setDeleting] = useState(false);
    const lastStepIndex = data.steps.length - 1;

    const preview = useMemo(
        () =>
            buildChainPreview(
                data.steps.map((s) => ({
                    approver_type: s.approver_type,
                    approver_id: s.approver_id,
                    step_mode: s.step_mode,
                })),
                (type, id) => {
                    if (!id) {
                        return undefined;
                    }

                    if (type === 'role') {
                        return roles.find((r) => String(r.id) === String(id))
                            ?.name;
                    }

                    if (type === 'department') {
                        return departments.find(
                            (d) => String(d.id) === String(id),
                        )?.name;
                    }

                    return data.steps.find(
                        (s) =>
                            s.approver_type === 'user' && s.approver_id === id,
                    )?.approver_label;
                },
            ),
        [data.steps, roles, departments],
    );

    function addStep() {
        setData('steps', [
            ...data.steps,
            {
                approver_type: 'role',
                approver_id: '',
                approver_label: '',
                step_mode: 'sequential',
            },
        ]);
    }

    function removeStep(index: number) {
        if (data.steps.length <= 1) {
            return;
        }

        setData(
            'steps',
            data.steps.filter((_, i) => i !== index),
        );
    }

    function updateStep(index: number, p: Partial<StepData>) {
        const updated = [...data.steps];
        updated[index] = { ...updated[index], ...p };
        setData('steps', updated);
    }

    function submit(e: FormEvent) {
        e.preventDefault();
        patch(ApprovalPolicyController.update.url(policy.id), {
            preserveScroll: true,
        });
    }

    function handleConfirmDelete() {
        if (!can.delete) {
            return;
        }

        setDeleting(true);
        router.delete(ApprovalPolicyController.destroy.url(policy.id), {
            onFinish: () => {
                setDeleting(false);
                setShowDeleteDialog(false);
            },
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar política — ${policy.name}`} />
            <div className="mx-auto flex w-full max-w-3xl animate-fade-in flex-col gap-4 p-4">
                <Heading
                    title="Editar política de aprobación"
                    description="Ajuste a quién aplica y los pasos de aprobación."
                />
                <form onSubmit={submit} className="flex flex-col gap-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Datos generales</CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-5">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nombre</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) =>
                                        setData('name', e.target.value)
                                    }
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label>Tipo de documento</Label>
                                    <Select
                                        value={data.document_type}
                                        onValueChange={(v) =>
                                            setData('document_type', v)
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {documentTypes.map((dt) => (
                                                <SelectItem
                                                    key={dt.value}
                                                    value={dt.value}
                                                >
                                                    {dt.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="version">Versión</Label>
                                    <Input
                                        id="version"
                                        type="number"
                                        min={1}
                                        value={data.version}
                                        onChange={(e) =>
                                            setData(
                                                'version',
                                                parseInt(e.target.value) || 1,
                                            )
                                        }
                                        required
                                    />
                                </div>
                            </div>
                            <div className="grid gap-3">
                                <Label>¿A quién aplica esta política?</Label>
                                <RadioGroup
                                    value={data.applies_to_kind}
                                    onValueChange={(v) => {
                                        setData(
                                            'applies_to_kind',
                                            v as AppliesToType,
                                        );
                                        setData('applies_to_id', '');
                                        setData('applies_to_user_label', '');
                                    }}
                                    className="flex flex-col gap-2"
                                >
                                    <div className="flex items-center gap-2">
                                        <RadioGroupItem
                                            value="all"
                                            id="applies-all"
                                        />
                                        <Label htmlFor="applies-all">
                                            A todos (por defecto)
                                        </Label>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <RadioGroupItem
                                            value="role"
                                            id="applies-role"
                                        />
                                        <Label htmlFor="applies-role">
                                            A un rol específico
                                        </Label>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <RadioGroupItem
                                            value="department"
                                            id="applies-dep"
                                        />
                                        <Label htmlFor="applies-dep">
                                            A un departamento
                                        </Label>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <RadioGroupItem
                                            value="user"
                                            id="applies-user"
                                        />
                                        <Label htmlFor="applies-user">
                                            A un usuario específico
                                        </Label>
                                    </div>
                                </RadioGroup>
                                {data.applies_to_kind === 'role' ? (
                                    <Select
                                        value={data.applies_to_id}
                                        onValueChange={(v) =>
                                            setData('applies_to_id', v)
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Selecciona un rol" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {roles.map((r) => (
                                                <SelectItem
                                                    key={r.id}
                                                    value={String(r.id)}
                                                >
                                                    {r.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                ) : null}
                                {data.applies_to_kind === 'department' ? (
                                    <Select
                                        value={data.applies_to_id}
                                        onValueChange={(v) =>
                                            setData('applies_to_id', v)
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Selecciona un departamento" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {departments.map((d) => (
                                                <SelectItem
                                                    key={d.id}
                                                    value={String(d.id)}
                                                >
                                                    {d.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                ) : null}
                                {data.applies_to_kind === 'user' ? (
                                    <ApproverPicker
                                        value={{
                                            type: 'user',
                                            id: data.applies_to_id,
                                            label: data.applies_to_user_label,
                                        }}
                                        roles={roles}
                                        departments={departments}
                                        userSearchUrl={userSearchUrl}
                                        onChange={(v) => {
                                            setData('applies_to_id', v.id);
                                            setData(
                                                'applies_to_user_label',
                                                v.label ?? '',
                                            );
                                        }}
                                    />
                                ) : null}
                                <InputError message={errors.applies_to_id} />
                            </div>
                            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="effective_from">
                                        Vigencia desde
                                    </Label>
                                    <Input
                                        id="effective_from"
                                        type="date"
                                        value={data.effective_from}
                                        onChange={(e) =>
                                            setData(
                                                'effective_from',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="effective_to">
                                        Vigencia hasta
                                    </Label>
                                    <Input
                                        id="effective_to"
                                        type="date"
                                        value={data.effective_to}
                                        onChange={(e) =>
                                            setData(
                                                'effective_to',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>
                            </div>
                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="is_active"
                                    checked={data.is_active}
                                    onCheckedChange={(v) =>
                                        setData('is_active', v === true)
                                    }
                                />
                                <Label htmlFor="is_active">
                                    Política activa
                                </Label>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <CardTitle>Quién aprueba</CardTitle>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={addStep}
                                >
                                    <Plus className="mr-1 size-4" />
                                    Agregar paso
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-4">
                            <InputError message={errors.steps} />
                            {data.steps.map((step, index) => (
                                <div
                                    key={index}
                                    className="flex flex-col gap-3 rounded-lg border p-4"
                                >
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm font-semibold">
                                            Paso {index + 1}
                                        </span>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            disabled={data.steps.length <= 1}
                                            onClick={() => removeStep(index)}
                                        >
                                            <Trash2 className="size-4 text-destructive" />
                                        </Button>
                                    </div>
                                    <ApproverPicker
                                        value={{
                                            type: step.approver_type,
                                            id: step.approver_id,
                                            label: step.approver_label,
                                        }}
                                        roles={roles}
                                        departments={departments}
                                        userSearchUrl={userSearchUrl}
                                        onChange={(v) =>
                                            updateStep(index, {
                                                approver_type: v.type,
                                                approver_id: v.id,
                                                approver_label: v.label ?? '',
                                            })
                                        }
                                    />
                                    {index < lastStepIndex ? (
                                        <div className="flex flex-col gap-2 border-t pt-3">
                                            <Label className="text-xs text-muted-foreground">
                                                Y después del paso {index + 1}…
                                            </Label>
                                            <RadioGroup
                                                value={step.step_mode}
                                                onValueChange={(v) =>
                                                    updateStep(index, {
                                                        step_mode: v,
                                                    })
                                                }
                                                className="flex flex-col gap-1"
                                            >
                                                {stepModes.map((m) => (
                                                    <div
                                                        key={m.value}
                                                        className="flex items-start gap-2"
                                                    >
                                                        <RadioGroupItem
                                                            value={m.value}
                                                            id={`mode-${index}-${m.value}`}
                                                            className="mt-1"
                                                        />
                                                        <Label
                                                            htmlFor={`mode-${index}-${m.value}`}
                                                            className="text-sm font-normal"
                                                        >
                                                            {m.label}
                                                        </Label>
                                                    </div>
                                                ))}
                                            </RadioGroup>
                                        </div>
                                    ) : null}
                                </div>
                            ))}
                            <div className="rounded-md border bg-muted/30 p-3">
                                <p className="text-xs tracking-wide text-muted-foreground uppercase">
                                    Vista previa
                                </p>
                                <p className="mt-1 text-sm">{preview}</p>
                            </div>
                        </CardContent>
                    </Card>
                    <div className="flex flex-wrap gap-2">
                        <Button type="submit" disabled={processing}>
                            Guardar cambios
                        </Button>
                        <Button variant="outline" type="button" asChild>
                            <Link href={ApprovalPolicyController.index.url()}>
                                Volver
                            </Link>
                        </Button>
                        {can.delete ? (
                            <Button
                                type="button"
                                variant="destructive"
                                className="ms-auto"
                                onClick={() => setShowDeleteDialog(true)}
                            >
                                Eliminar
                            </Button>
                        ) : null}
                    </div>
                </form>
            </div>
            <ConfirmationDialog
                open={showDeleteDialog}
                onOpenChange={(open) => {
                    if (!open) {
                        setShowDeleteDialog(false);
                    }
                }}
                title="Eliminar política"
                description="¿Eliminar esta política de aprobación?"
                confirmLabel="Eliminar"
                variant="destructive"
                processing={deleting}
                onConfirm={handleConfirmDelete}
            />
        </AppLayout>
    );
}
