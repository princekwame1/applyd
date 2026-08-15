<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An invoice, a receipt or any other paper backing one entry in the books.
 * The file lives on the private `local` disk — it is only ever served through
 * the authorised dashboard download route, never from a public URL.
 */
class FinanceDocument extends Model
{
    public const INVOICE = 'invoice';

    public const RECEIPT = 'receipt';

    public const OTHER = 'other';

    public const KINDS = [
        self::INVOICE => 'Invoice',
        self::RECEIPT => 'Receipt',
        self::OTHER => 'Other document',
    ];

    /** What we accept, and how big. Kept here so form, rules and hint agree. */
    public const MIMES = 'pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx';

    public const MAX_KB = 8192;

    protected $fillable = [
        'finance_transaction_id',
        'kind',
        'path',
        'original_name',
        'mime_type',
        'size',
        'uploaded_by',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(FinanceTransaction::class, 'finance_transaction_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? $this->kind;
    }

    public function humanSize(): string
    {
        $bytes = (int) $this->size;

        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024).' KB';
        }

        return round($bytes / 1048576, 1).' MB';
    }
}
