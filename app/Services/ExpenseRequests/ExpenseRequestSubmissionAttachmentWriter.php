<?php

namespace App\Services\ExpenseRequests;

use App\Models\Attachment;
use App\Models\ExpenseRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class ExpenseRequestSubmissionAttachmentWriter
{
    /**
     * @param  array<int, mixed>  $files
     */
    public function attachMany(ExpenseRequest $expenseRequest, User $actor, array $files): void
    {
        $uploads = array_values(array_filter(
            $files,
            fn (mixed $f): bool => $f instanceof UploadedFile,
        ));

        foreach ($uploads as $file) {
            /** @var UploadedFile $file */
            $path = $file->store('expense-request-submissions/'.$expenseRequest->getKey(), 'local');
            if ($path === false) {
                throw new RuntimeException(__('No se pudo guardar un archivo adjunto.'));
            }

            Attachment::query()->create([
                'attachable_type' => $expenseRequest->getMorphClass(),
                'attachable_id' => $expenseRequest->getKey(),
                'uploaded_by_user_id' => $actor->id,
                'disk' => 'local',
                'path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType() ?? 'application/octet-stream',
                'size_bytes' => $file->getSize(),
            ]);
        }
    }

    public function attachmentBelongsToExpenseRequest(ExpenseRequest $expenseRequest, Attachment $attachment): bool
    {
        return $attachment->attachable_type === $expenseRequest->getMorphClass()
            && (int) $attachment->attachable_id === (int) $expenseRequest->getKey();
    }

    public function deleteAttachment(ExpenseRequest $expenseRequest, Attachment $attachment): void
    {
        if (! $this->attachmentBelongsToExpenseRequest($expenseRequest, $attachment)) {
            abort(404);
        }

        DB::transaction(function () use ($attachment): void {
            Storage::disk($attachment->disk)->delete($attachment->path);
            $attachment->delete();
        });
    }
}
