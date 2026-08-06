<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsLog extends Model
{
    protected $fillable = [
        'registration_id',
        'name',
        'phone_number',
        'message',
        'status',
        'response',
        'external_id',
        'sent_at',
        'retry_count',
        'last_retry_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'last_retry_at' => 'datetime',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }
}
