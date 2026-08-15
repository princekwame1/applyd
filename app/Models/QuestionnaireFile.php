<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One upload attached to one answer. The file itself lives on the private
 * `local` disk — it is only ever served through the authorised dashboard
 * download route, never from a public URL.
 */
class QuestionnaireFile extends Model
{
    protected $fillable = [
        'questionnaire_response_id',
        'question_key',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    public function response(): BelongsTo
    {
        return $this->belongsTo(QuestionnaireResponse::class, 'questionnaire_response_id');
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
