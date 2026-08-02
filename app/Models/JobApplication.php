<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobApplication extends Model
{
    public const STATUSES = ['pending', 'reviewed', 'shortlisted', 'rejected'];

    protected $fillable = [
        'job_opening_id',
        'full_name',
        'email',
        'phone',
        'cover_letter',
        'cv_path',
        'cv_name',
        'status',
    ];

    public function opening(): BelongsTo
    {
        return $this->belongsTo(JobOpening::class, 'job_opening_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }
}
