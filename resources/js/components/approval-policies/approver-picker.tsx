import { useEffect, useState } from 'react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export type ApproverOption = { id: number; name: string };

export type ApproverPickerValue = {
    type: 'role' | 'department' | 'user';
    id: string;
    label?: string;
};

type UserSearchResult = {
    id: number;
    name: string;
    email: string;
    role_label: string | null;
};

export function ApproverPicker({
    value,
    roles,
    departments,
    userSearchUrl,
    onChange,
}: {
    value: ApproverPickerValue;
    roles: ApproverOption[];
    departments: ApproverOption[];
    userSearchUrl: string;
    onChange: (v: ApproverPickerValue) => void;
}) {
    const [userQuery, setUserQuery] = useState('');
    const [userResults, setUserResults] = useState<UserSearchResult[]>([]);
    const [userLoading, setUserLoading] = useState(false);
    const [userPickerLabel, setUserPickerLabel] = useState(value.label ?? '');

    useEffect(() => {
        if (value.type !== 'user') {
            setUserResults([]);
            return;
        }
        if (userQuery.trim().length < 1) {
            setUserResults([]);
            return;
        }

        const ctrl = new AbortController();
        const timeout = window.setTimeout(async () => {
            setUserLoading(true);
            try {
                const url = new URL(userSearchUrl, window.location.origin);
                url.searchParams.set('q', userQuery);
                url.searchParams.set('limit', '20');
                const res = await fetch(url.toString(), {
                    signal: ctrl.signal,
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' },
                });
                if (res.ok) {
                    const data = await res.json();
                    setUserResults(data.users ?? []);
                }
            } finally {
                setUserLoading(false);
            }
        }, 250);

        return () => {
            ctrl.abort();
            window.clearTimeout(timeout);
        };
    }, [userQuery, value.type, userSearchUrl]);

    return (
        <div className="flex flex-col gap-3">
            <div className="flex flex-wrap items-center gap-2">
                <Label className="text-xs text-muted-foreground">
                    Aprueba:
                </Label>
                <div className="flex gap-1 rounded-md border p-1">
                    {(['role', 'department', 'user'] as const).map((t) => (
                        <button
                            type="button"
                            key={t}
                            onClick={() =>
                                onChange({ type: t, id: '', label: '' })
                            }
                            className={
                                'rounded px-2 py-1 text-xs ' +
                                (value.type === t
                                    ? 'bg-primary text-primary-foreground'
                                    : 'hover:bg-muted')
                            }
                        >
                            {t === 'role'
                                ? 'Rol'
                                : t === 'department'
                                  ? 'Departamento'
                                  : 'Usuario'}
                        </button>
                    ))}
                </div>
            </div>
            {value.type === 'role' ? (
                <Select
                    value={value.id}
                    onValueChange={(v) => {
                        const r = roles.find((r) => String(r.id) === v);
                        onChange({
                            type: 'role',
                            id: v,
                            label: r?.name,
                        });
                    }}
                >
                    <SelectTrigger>
                        <SelectValue placeholder="Selecciona un rol" />
                    </SelectTrigger>
                    <SelectContent>
                        {roles.map((r) => (
                            <SelectItem key={r.id} value={String(r.id)}>
                                {r.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            ) : value.type === 'department' ? (
                <Select
                    value={value.id}
                    onValueChange={(v) => {
                        const d = departments.find(
                            (d) => String(d.id) === v,
                        );
                        onChange({
                            type: 'department',
                            id: v,
                            label: d?.name,
                        });
                    }}
                >
                    <SelectTrigger>
                        <SelectValue placeholder="Selecciona un departamento" />
                    </SelectTrigger>
                    <SelectContent>
                        {departments.map((d) => (
                            <SelectItem key={d.id} value={String(d.id)}>
                                {d.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            ) : (
                <div className="flex flex-col gap-1">
                    {value.id ? (
                        <div className="flex items-center justify-between gap-2 rounded-md border bg-muted/50 px-3 py-2">
                            <span className="text-sm">
                                {userPickerLabel || `Usuario #${value.id}`}
                            </span>
                            <button
                                type="button"
                                className="text-xs text-muted-foreground hover:text-foreground"
                                onClick={() => {
                                    onChange({
                                        type: 'user',
                                        id: '',
                                        label: '',
                                    });
                                    setUserPickerLabel('');
                                    setUserQuery('');
                                }}
                            >
                                Cambiar
                            </button>
                        </div>
                    ) : (
                        <>
                            <Input
                                value={userQuery}
                                onChange={(e) =>
                                    setUserQuery(e.target.value)
                                }
                                placeholder="Buscar usuario por nombre o email…"
                            />
                            {userLoading ? (
                                <p className="text-xs text-muted-foreground">
                                    Buscando…
                                </p>
                            ) : null}
                            {userResults.length > 0 ? (
                                <ul className="flex max-h-60 flex-col gap-1 overflow-y-auto rounded-md border p-1">
                                    {userResults.map((u) => (
                                        <li key={u.id}>
                                            <button
                                                type="button"
                                                className="flex w-full flex-col items-start rounded px-2 py-1 text-left text-sm hover:bg-muted"
                                                onClick={() => {
                                                    const label = u.role_label
                                                        ? `${u.name} (${u.role_label})`
                                                        : u.name;
                                                    onChange({
                                                        type: 'user',
                                                        id: String(u.id),
                                                        label,
                                                    });
                                                    setUserPickerLabel(label);
                                                }}
                                            >
                                                <span>{u.name}</span>
                                                <span className="text-xs text-muted-foreground">
                                                    {u.email}
                                                    {u.role_label
                                                        ? ` · ${u.role_label}`
                                                        : ''}
                                                </span>
                                            </button>
                                        </li>
                                    ))}
                                </ul>
                            ) : null}
                        </>
                    )}
                </div>
            )}
        </div>
    );
}
