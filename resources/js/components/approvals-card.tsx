import { StatusBadge } from '@/components/status-badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { cn } from '@/lib/utils';
import type { ApprovalProgress, ApprovalRow } from '@/types';

export default function ApprovalsCard({
    approvals,
    progress,
}: {
    approvals: ApprovalRow[];
    progress: ApprovalProgress | null;
}) {
    const progressPercent =
        progress && progress.total_groups > 0
            ? Math.round(
                  (progress.completed_groups / progress.total_groups) * 100,
              )
            : 0;

    return (
        <Card>
            <CardHeader>
                <CardTitle>Aprobaciones</CardTitle>
                {progress && (
                    <CardDescription>
                        {progress.completed_groups} de {progress.total_groups}{' '}
                        grupos completados
                        {progress.remaining_groups > 0 &&
                            ` · ${progress.remaining_groups} pendiente(s)`}
                    </CardDescription>
                )}
            </CardHeader>
            <CardContent>
                {progress && (
                    <div className="mb-4">
                        <div className="h-2 overflow-hidden rounded-full bg-muted">
                            <div
                                className="h-full rounded-full bg-primary transition-all"
                                style={{ width: `${progressPercent}%` }}
                            />
                        </div>
                    </div>
                )}
                <ul className="space-y-2">
                    {approvals.map((a) => (
                        <li
                            key={a.id}
                            className="flex items-start gap-3 rounded-md border px-3 py-2.5 text-sm"
                        >
                            <div
                                className={cn(
                                    'mt-0.5 size-2 shrink-0 rounded-full',
                                    a.status === 'approved'
                                        ? 'bg-emerald-500'
                                        : a.status === 'rejected'
                                          ? 'bg-red-500'
                                          : 'bg-amber-400',
                                )}
                            />
                            <div className="min-w-0 flex-1">
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <span className="font-medium">
                                        Paso {a.step_order} — {a.role.name}
                                    </span>
                                    <StatusBadge
                                        status={a.status}
                                        className="text-xs"
                                    />
                                </div>
                                {a.note && (
                                    <p className="mt-1 text-muted-foreground">
                                        {a.note}
                                    </p>
                                )}
                            </div>
                        </li>
                    ))}
                </ul>
            </CardContent>
        </Card>
    );
}
