import { router, useForm } from '@inertiajs/react';
import { Download, FileText, Trash2, Upload } from 'lucide-react';
import { useState } from 'react';
import ExpenseRequestController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestController';
import ConfirmationDialog from '@/components/confirmation-dialog';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { SubmissionAttachmentRow } from './types';

type DownloadLink = {
    label: string;
    href: string;
};

export default function DocumentsSection({
    expenseRequestId,
    attachments,
    canAddAttachments,
    downloads,
}: {
    expenseRequestId: number;
    attachments: SubmissionAttachmentRow[];
    canAddAttachments: boolean;
    downloads: DownloadLink[];
}) {
    const form = useForm<{ attachments: File[] }>({ attachments: [] });
    const [deleteTarget, setDeleteTarget] = useState<number | null>(null);

    const hasContent =
        attachments.length > 0 || downloads.length > 0 || canAddAttachments;

    if (!hasContent) return null;

    return (
        <div className="space-y-4">
            {downloads.length > 0 && (
                <div className="space-y-2">
                    <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                        Recibos y acuses
                    </p>
                    <div className="flex flex-wrap gap-2">
                        {downloads.map((dl) => (
                            <Button
                                key={dl.label}
                                variant="outline"
                                size="sm"
                                className="min-h-10"
                                asChild
                            >
                                <a href={dl.href}>
                                    <Download className="mr-1.5 size-3.5" />
                                    {dl.label}
                                </a>
                            </Button>
                        ))}
                    </div>
                </div>
            )}

            {(attachments.length > 0 || canAddAttachments) && (
                <div className="space-y-2">
                    <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                        Archivos adjuntos
                    </p>
                    {attachments.length > 0 && (
                        <ul className="space-y-1">
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
                                                className="size-8"
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
                                                className="size-8 text-destructive hover:text-destructive"
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

                    {canAddAttachments && (
                        <form
                            className="flex items-end gap-3 pt-2"
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
                                        onSuccess: () =>
                                            form.reset('attachments'),
                                    },
                                );
                            }}
                        >
                            <div className="flex-1 space-y-1">
                                <Label htmlFor="doc_add_files">
                                    Añadir archivos
                                </Label>
                                <Input
                                    id="doc_add_files"
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
                                <InputError
                                    message={form.errors.attachments}
                                />
                            </div>
                            <Button
                                type="submit"
                                size="sm"
                                className="min-h-10"
                                disabled={form.processing}
                            >
                                <Upload className="mr-1.5 size-3.5" />
                                {form.processing ? 'Subiendo…' : 'Subir'}
                            </Button>
                        </form>
                    )}
                </div>
            )}

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
        </div>
    );
}
