<?php

namespace App\Services\ExpenseReports;

use App\Models\Attachment;
use App\Models\ExpenseReport;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class ExpenseReportAttachmentWriter
{
    public function removeKind(ExpenseReport $report, string $kind): void
    {
        $report->loadMissing('attachments');

        foreach ($report->attachments as $attachment) {
            if (! $this->attachmentMatchesKind($attachment, $kind)) {
                continue;
            }

            Storage::disk($attachment->disk)->delete($attachment->path);
            $attachment->delete();
        }
    }

    public function storeKind(
        ExpenseReport $report,
        User $actor,
        UploadedFile $file,
        string $kind,
    ): void {
        $this->removeKind($report, $kind);

        $directory = 'expense-reports/'.$report->getKey().'/'.$kind;
        $path = $file->store($directory, 'local');
        if ($path === false) {
            throw new \RuntimeException(__('No se pudo guardar el archivo de comprobación.'));
        }

        Attachment::query()->create([
            'attachable_type' => $report->getMorphClass(),
            'attachable_id' => $report->getKey(),
            'uploaded_by_user_id' => $actor->id,
            'disk' => 'local',
            'path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
        ]);
    }

    public function hasPdfAndXml(ExpenseReport $report): bool
    {
        $report->loadMissing('attachments');

        $hasPdf = false;
        $hasXml = false;

        foreach ($report->attachments as $attachment) {
            if ($this->attachmentMatchesKind($attachment, 'pdf')) {
                $hasPdf = true;
            }
            if ($this->attachmentMatchesKind($attachment, 'xml')) {
                $hasXml = true;
            }
        }

        return $hasPdf && $hasXml;
    }

    public function findVerificationAttachment(ExpenseReport $report, string $kind): ?Attachment
    {
        $report->loadMissing('attachments');

        foreach ($report->attachments as $attachment) {
            if ($this->attachmentMatchesKind($attachment, $kind)) {
                return $attachment;
            }
        }

        return null;
    }

    private function attachmentMatchesKind(Attachment $attachment, string $kind): bool
    {
        $mime = strtolower((string) $attachment->mime_type);
        $name = strtolower((string) $attachment->original_filename);

        return match ($kind) {
            'pdf' => str_contains($mime, 'pdf') || str_ends_with($name, '.pdf'),
            'xml' => str_contains($mime, 'xml') || str_ends_with($name, '.xml'),
            default => false,
        };
    }
}
