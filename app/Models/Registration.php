<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Registration extends Model
{
    protected $fillable = [
        'full_name',
        'gender',
        'age_range',
        'country',
        'city',
        'phone_country_code',
        'phone',
        'email',
        'education',
        'tools',
        'marketing_opt_in',
    ];

    protected function casts(): array
    {
        return [
            'tools' => 'array',
            'marketing_opt_in' => 'boolean',
        ];
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class);
    }

    public function smsLogs(): HasMany
    {
        return $this->hasMany(SmsLog::class);
    }

    public function getFullPhoneAttribute(): string
    {
        return $this->phone_country_code.' '.$this->phone;
    }
}
