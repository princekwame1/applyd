<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CourseEnrollment extends Model
{
    protected $fillable = [
        'course_id',
        'attendance_type',
        'name',
        'email',
        'phone',
        'amount',
        'amount_fee',
        'tuition_fee',
        'reference',
        'pay_token',
        'student_id',
        'user_id',
        'credentials_sent_at',
        'form_reminder_sent_at',
        'tuition_reminder_sent_at',
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
        'tuition_option',
        'tuition_amount',
        'tuition_status',
        'tuition_reference',
        'tuition_paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_fee' => 'decimal:2',
        'tuition_fee' => 'decimal:2',
        'paid_at' => 'datetime',
        'date_of_birth' => 'date',
        'completed_at' => 'datetime',
        'tuition_amount' => 'decimal:2',
        'tuition_paid_at' => 'datetime',
        'credentials_sent_at' => 'datetime',
        'form_reminder_sent_at' => 'datetime',
        'tuition_reminder_sent_at' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** The shared account this registration issued (or was claimed by). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    public function hasDetails(): bool
    {
        return $this->date_of_birth !== null && $this->gender !== null && $this->education_level !== null;
    }

    public function getTuitionPaidAttribute(): float
    {
        return (float) $this->tuition_amount;
    }

    /** Full tuition for this enrollment's chosen attendance mode. */
    public function tuitionFull(): float
    {
        return $this->course ? $this->course->priceForAttendance($this->attendance_type) : 0;
    }

    public function tuitionBalance(): float
    {
        return max(0, round($this->tuitionFull() - $this->tuition_paid, 2));
    }

    public function getAttendanceLabelAttribute(): string
    {
        if (! $this->attendance_type) {
            return '—';
        }

        return $this->course
            ? $this->course->attendanceLabel($this->attendance_type)
            : (string) Str::of($this->attendance_type)->replace('-', ' ')->title();
    }

    public function getTuitionStatusLabelAttribute(): string
    {
        return match ($this->tuition_status) {
            'paid' => 'Paid in full',
            'partial' => 'Part payment (50%)',
            'pending' => 'Payment pending',
            default => 'Unpaid',
        };
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

    /**
     * The handle the payment link is built from, minted on demand so a row
     * created before this feature (or by a seeder) still gets a working link
     * the first time someone reminds them.
     */
    public function payToken(): string
    {
        if (! $this->pay_token) {
            // 12 lowercase alphanumerics: 36^12 combinations is far past
            // guessable, and every character spent here is a character the
            // 160-character reminder SMS does not get to use.
            do {
                $token = Str::lower(Str::random(12));
            } while (static::where('pay_token', $token)->exists());

            $this->forceFill(['pay_token' => $token])->save();
        }

        return $this->pay_token;
    }

    public function payUrl(): string
    {
        return route('enroll.pay', $this->payToken());
    }

    /** Money still owed on the application form itself. */
    public function owesFormFee(): bool
    {
        return $this->status !== 'paid';
    }

    /**
     * Money still owed on tuition. A course with no tuition is never owed, and
     * neither is someone who has not paid the form fee yet — they get the form
     * reminder instead, and reminding for both at once is just noise.
     */
    public function owesTuition(): bool
    {
        if ($this->owesFormFee()) {
            return false;
        }

        if (! $this->course || ! $this->course->requiresTuition()) {
            return false;
        }

        return $this->tuitionBalance() > 0;
    }

    public function scopeFormPaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }

    public function scopeFormUnpaid(Builder $query): Builder
    {
        return $query->where('status', '!=', 'paid');
    }

    public function scopeTuitionPaid(Builder $query): Builder
    {
        return $query->where('tuition_status', 'paid');
    }

    /**
     * Everyone who has paid the form fee but still owes tuition. The balance
     * itself depends on the course price, which is not a column here, so this
     * narrows in SQL and the caller confirms with owesTuition().
     */
    public function scopeTuitionOutstanding(Builder $query): Builder
    {
        return $query->where('status', 'paid')->where('tuition_status', '!=', 'paid');
    }
}
