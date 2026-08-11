<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
{
    protected $fillable = [
        'registration_id',
        'template_key',
        'name',
        'email',
        'subject',
        'body',
        'heading',
        'cta_label',
        'cta_url',
        'status',
        'response',
        'sent_at',
        'retry_count',
        'last_retry_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'last_retry_at' => 'datetime',
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function getTemplateLabelAttribute(): string
    {
        return config("email_templates.templates.{$this->template_key}.label", $this->template_key ?? 'Custom');
    }
}
