<?php

namespace App\Support;

use App\Models\FinanceDocument;
use App\Models\FinanceTransaction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Storing and removing the paperwork behind an entry. One place, because the
 * entry form and the "add another document" form on the detail page must put
 * files in the same spot and clean them up the same way.
 */
class FinanceDocuments
{
    /**
     * An entry has one invoice and one receipt, so uploading either replaces
     * what was there — re-uploading is how you correct a wrong scan, and a
     * silent second copy nobody sees would be worse than useless. Anything
     * filed as "other" simply accumulates.
     */
    public static function store(
        UploadedFile $file,
        FinanceTransaction $transaction,
        string $kind,
        ?int $userId = null,
    ): FinanceDocument {
        if ($kind !== FinanceDocument::OTHER) {
            foreach ($transaction->documents()->where('kind', $kind)->get() as $existing) {
                static::delete($existing);
            }
        }

        $document = $transaction->documents()->create([
            'kind' => $kind,
            // Private disk: a receipt is only ever served back through the
            // authorised dashboard download route.
            'path' => $file->store('finance/'.$transaction->id, 'local'),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => $userId,
        ]);

        // Kept fresh so a caller that already read ->documents sees the change.
        $transaction->unsetRelation('documents');

        return $document;
    }

    /** Row and file together — an orphaned file on disk is nobody's job later. */
    public static function delete(FinanceDocument $document): void
    {
        Storage::disk('local')->delete($document->path);

        $document->delete();
    }
}
