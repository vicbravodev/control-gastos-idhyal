import { router, useForm } from '@inertiajs/react';
import { Download, FileText, Paperclip, Trash2, Upload } from 'lucide-react';
import { useState } from 'react';
import ExpenseRequestController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestController';
import ConfirmationDialog from '@/components/confirmation-dialog';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { SubmissionAttachmentRow } from './types';

export default function SubmissionAttachmentsCard({
    expenseRequestId,
    attachments,
    canAdd,
}: {
    expenseRequestId: number;
    attachments: SubmissionAttachmentRow[];
    canAdd: boolean;
}) {
    const form = useForm<{ attachments: File[] }>({ attachments: [] });
    const [deleteTarget, setDeleteTarget] = useState<number | null>(null);

    return (
        <Card>
            <CardHeader>
                <CardTitle>
                    <Paperclip className="mr-2 inline-block size-4" />
                    Archivos adjuntos
                </CardTitle>
                <CardDescription>
                    Documentación opcional. No sustituye la comprobación
                    posterior.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                {attachments.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Sin adjuntos.
                    </p>
                ) : (
                    <ul className="space-y-2">
                        {attachments.map((row) => (
                            <li
                                key={row.id}
                                className="flex items-center justify-between gap-2 rounded-md border px-3 py-2 text-sm"
                            >
                                <div className="flex items-center gap-2 truncate">
                                    <FileText className="size-4 shrink-0 text-muted-foreground" />
                                    <span className="truncate">
                                        {row.original_filename}
                                    </span>
                                </div>
                                <div className="flex shrink-0 gap-1.5">
                                    {row.can_download && (
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="size-7"
                                            asChild
                                        >
                                            <a
                                                href={ExpenseRequestController.downloadSubmissionAttachment.url(
                                                    {
                                                        expense_request:
                                                            expenseRequestId,
                                                        attachment: row.id,
                                                    },
                                                )}
                                            >
                                                <Download className="size-3.5" />
                                            </a>
                                        </Button>
                                    )}
                                    {row.can_delete && (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            className="size-7 text-destructive hover:text-destructive"
                                            onClick={() =>
                                                setDeleteTarget(row.id)
                                            }
                                        >
                                            <Trash2 className="size-3.5" />
                                        </Button>
                                    )}
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
                {canAdd && (
                    <form
                        className="space-y-3 border-t pt-4"
                        onSubmit={(e) => {
                            e.preventDefault();
                            if (form.data.attachments.length === 0) return;
                            form.post(
                                ExpenseRequestController.storeSubmissionAttachments.url(
                                    expenseRequestId,
                                ),
                                {
                                    forceFormData: true,
                                    preserveScroll: true,
                                    onSuccess: () => form.reset('attachments'),
                                },
                            );
                        }}
                    >
                        <Label htmlFor="submission_attachments_add">
                            Añadir archivos
                        </Label>
                        <Input
                            id="submission_attachments_add"
                            type="file"
                            multiple
                            accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp"
                            className="cursor-pointer"
                            onChange={(ev) => {
                                const list = ev.target.files;
                                form.setData(
                                    'attachments',
                                    list ? Array.from(list) : [],
                                );
                            }}
                        />
                        <InputError message={form.errors.attachments} />
                        <Button
                            type="submit"
                            size="sm"
                            disabled={form.processing}
                        >
                            <Upload className="mr-1.5 size-3.5" />
                            {form.processing ? 'Subiendo…' : 'Subir'}
                        </Button>
                    </form>
                )}
            </CardContent>
            <ConfirmationDialog
                open={deleteTarget !== null}
                onOpenChange={(open) => {
                    if (!open) setDeleteTarget(null);
                }}
                title="¿Eliminar este archivo?"
                description="El archivo adjunto se eliminará permanentemente."
                confirmLabel="Eliminar"
                variant="destructive"
                onConfirm={() => {
                    if (deleteTarget === null) return;
                    router.delete(
                        ExpenseRequestController.destroySubmissionAttachment.url(
                            {
                                expense_request: expenseRequestId,
                                attachment: deleteTarget,
                            },
                        ),
                        { preserveScroll: true },
                    );
                    setDeleteTarget(null);
                }}
            />
        </Card>
    );
}
