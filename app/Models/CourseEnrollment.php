<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseEnrollment extends Model
{
    protected $fillable = [
        'course_id',
        'name',
        'email',
        'phone',
        'amount',
        'reference',
        'serial_no',
        'pin',
        'status',
        'paid_at',
        'date_of_birth',
        'gender',
        'country',
        'city',
        'education_level',
        'goals',
        'completed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'date_of_birth' => 'date',
        'completed_at' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function getAmountLabelAttribute(): string
    {
        return 'GHS '.number_format((float) $this->amount, 2);
    }

    public function getFirstNameAttribute(): string
    {
        return explode(' ', trim($this->name))[0] ?? $this->name;
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->completed_at !== null;
    }

    public static function generateSerial(): string
    {
        do {
            $serial = 'APPLYD'.random_int(1000000, 9999999);
        } while (static::where('serial_no', $serial)->exists());

        return $serial;
    }

    public static function generatePin(): string
    {
        return (string) random_int(1000000000, 9999999999);
    }
}
