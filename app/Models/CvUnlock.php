<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One spent credit: this company has paid to see this candidate, permanently.
 */
class CvUnlock extends Model
{
    protected $fillable = [
        'company_id',
        'talent_profile_id',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(TalentProfile::class, 'talent_profile_id');
    }
}
