import { StatusBadge } from '@/components/status-badge';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { formatCentsMx } from '@/lib/money';
import { DataRow } from './types';

export default function RequestDetailCard({
    status,
    requestedAmountCents,
    approvedAmountCents,
    deliveryMethod,
    conceptLabel,
    conceptDescription,
}: {
    status: string;
    requestedAmountCents: number;
    approvedAmountCents: number | null;
    deliveryMethod: string;
    conceptLabel: string;
    conceptDescription: string | null;
}) {
    const deliveryLabel =
        deliveryMethod === 'cash'
            ? 'Efectivo'
            : deliveryMethod === 'transfer'
              ? 'Transferencia'
              : deliveryMethod;

    return (
        <Card>
            <CardHeader>
                <CardTitle>Datos de la solicitud</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="divide-y">
                    <DataRow label="Estado">
                        <StatusBadge status={status} />
                    </DataRow>
                    <DataRow label="Monto solicitado">
                        <span className="tabular-nums">
                            {formatCentsMx(requestedAmountCents)}
                        </span>
                    </DataRow>
                    {approvedAmountCents !== null && (
                        <DataRow label="Monto aprobado">
                            <span className="tabular-nums">
                                {formatCentsMx(approvedAmountCents)}
                            </span>
                        </DataRow>
                    )}
                    <DataRow label="Entrega">{deliveryLabel}</DataRow>
                </div>
                <Separator className="my-4" />
                <div>
                    <p className="mb-2 text-xs font-medium uppercase tracking-wider text-muted-foreground">
                        Concepto
                    </p>
                    <p className="text-sm font-medium">{conceptLabel}</p>
                    {conceptDescription ? (
                        <p className="mt-2 whitespace-pre-wrap text-sm text-muted-foreground">
                            {conceptDescription}
                        </p>
                    ) : null}
                </div>
            </CardContent>
        </Card>
    );
}
