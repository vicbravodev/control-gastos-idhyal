import { Head, Link, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import ApprovalPolicyController from '@/actions/App/Http/Controllers/ApprovalPolicies/ApprovalPolicyController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type RoleOption = { id: number; name: string };
type DocumentTypeOption = { value: string; label: string };

type StepData = {
    role_id: string;
    combine_with_next: string;
};

type PolicyFormData = {
    document_type: string;
    name: string;
    version: number;
    requester_role_id: string;
    effective_from: string;
    effective_to: string;
    is_active: boolean;
    steps: StepData[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    {
        title: 'Políticas de aprobación',
        href: ApprovalPolicyController.index.url(),
    },
    { title: 'Nueva', href: ApprovalPolicyController.create.url() },
];

export default function ApprovalPoliciesCreate({
    roles,
    documentTypes,
}: {
    roles: RoleOption[];
    documentTypes: DocumentTypeOption[];
}) {
    const { data, setData, post, processing, errors } =
        useForm<PolicyFormData>({
            document_type: documentTypes[0]?.value ?? '',
            name: '',
            version: 1,
            requester_role_id: '',
            effective_from: '',
            effective_to: '',
            is_active: true,
            steps: [{ role_id: '', combine_with_next: 'and' }],
        });

    function addStep() {
        setData('steps', [
            ...data.steps,
            { role_id: '', combine_with_next: 'and' },
        ]);
    }

    function removeStep(index: number) {
        if (data.steps.length <= 1) return;
        setData(
            'steps',
            data.steps.filter((_, i) => i !== index),
        );
    }

    function updateStep(index: number, field: keyof StepData, value: string) {
        const updated = [...data.steps];
        updated[index] = { ...updated[index], [field]: value };
        setData('steps', updated);
    }

    function submit(e: FormEvent) {
        e.preventDefault();
        post(ApprovalPolicyController.store.url(), {
            preserveScroll: true,
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva política de aprobación" />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-4 p-4 animate-fade-in">
                <Heading
                    title="Nueva política de aprobación"
                    description="Define el nombre, tipo de documento y la cadena de pasos de aprobación."
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
                                    placeholder="Ej: Gastos — coordinador y contabilidad"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="document_type">
                                        Tipo de documento
                                    </Label>
                                    <Select
                                        value={data.document_type}
                                        onValueChange={(v) =>
                                            setData('document_type', v)
                                        }
                                    >
                                        <SelectTrigger id="document_type">
                                            <SelectValue placeholder="Selecciona" />
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
                                    <InputError
                                        message={errors.document_type}
                                    />
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
                                    <InputError message={errors.version} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="requester_role_id">
                                    Rol del solicitante (opcional)
                                </Label>
                                <Select
                                    value={data.requester_role_id}
                                    onValueChange={(v) =>
                                        setData(
                                            'requester_role_id',
                                            v === '__none__' ? '' : v,
                                        )
                                    }
                                >
                                    <SelectTrigger id="requester_role_id">
                                        <SelectValue placeholder="Todos los roles" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="__none__">
                                            Todos los roles
                                        </SelectItem>
                                        {roles.map((role) => (
                                            <SelectItem
                                                key={role.id}
                                                value={String(role.id)}
                                            >
                                                {role.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <p className="text-xs text-muted-foreground">
                                    Deja en &quot;Todos&quot; para usarla como
                                    política por defecto.
                                </p>
                                <InputError
                                    message={errors.requester_role_id}
                                />
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
                                    <InputError
                                        message={errors.effective_from}
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
                                    <InputError
                                        message={errors.effective_to}
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
                                <Label htmlFor="is_active" className="text-sm">
                                    Política activa
                                </Label>
                            </div>
                            <InputError message={errors.is_active} />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <CardTitle>Pasos de aprobación</CardTitle>
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
                                    className="flex items-start gap-3 rounded-lg border p-3"
                                >
                                    <span className="mt-2 flex size-6 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-medium">
                                        {index + 1}
                                    </span>
                                    <div className="flex flex-1 flex-col gap-3 sm:flex-row sm:items-start">
                                        <div className="grid flex-1 gap-1">
                                            <Label className="text-xs text-muted-foreground">
                                                Rol aprobador
                                            </Label>
                                            <Select
                                                value={step.role_id}
                                                onValueChange={(v) =>
                                                    updateStep(
                                                        index,
                                                        'role_id',
                                                        v,
                                                    )
                                                }
                                            >
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Selecciona rol" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {roles.map((role) => (
                                                        <SelectItem
                                                            key={role.id}
                                                            value={String(
                                                                role.id,
                                                            )}
                                                        >
                                                            {role.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={
                                                    errors[
                                                        `steps.${index}.role_id` as keyof typeof errors
                                                    ]
                                                }
                                            />
                                        </div>
                                        <div className="grid w-full gap-1 sm:w-32">
                                            <Label className="text-xs text-muted-foreground">
                                                Combinar
                                            </Label>
                                            <Select
                                                value={step.combine_with_next}
                                                onValueChange={(v) =>
                                                    updateStep(
                                                        index,
                                                        'combine_with_next',
                                                        v,
                                                    )
                                                }
                                            >
                                                <SelectTrigger>
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="and">
                                                        Y (AND)
                                                    </SelectItem>
                                                    <SelectItem value="or">
                                                        O (OR)
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        className="mt-5 shrink-0"
                                        disabled={data.steps.length <= 1}
                                        onClick={() => removeStep(index)}
                                    >
                                        <Trash2 className="size-4 text-destructive" />
                                        <span className="sr-only">
                                            Eliminar paso
                                        </span>
                                    </Button>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <div className="flex flex-wrap gap-2">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Guardando…' : 'Crear política'}
                        </Button>
                        <Button variant="outline" type="button" asChild>
                            <Link
                                href={ApprovalPolicyController.index.url()}
                            >
                                Cancelar
                            </Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
