<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * One purchase of one digital product.
 *
 * The row exists before the buyer ever reaches Paystack, so an abandoned
 * checkout is still traceable, and `download_token` is minted with it: the
 * token is the buyer's credential, the only thing that opens the file, which
 * is why it is long, random and unique.
 */
class ProductOrder extends Model
{
    protected $fillable = [
        'digital_product_id',
        'product_title',
        'buyer_name',
        'buyer_email',
        'buyer_phone',
        'amount',
        'fee',
        'reference',
        'status',
        'paid_at',
        'download_token',
        'download_count',
        'last_downloaded_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'paid_at' => 'datetime',
        'download_count' => 'integer',
        'last_downloaded_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(DigitalProduct::class, 'digital_product_id');
    }

    /** Only a settled payment opens a download — pending and failed never do. */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public static function newToken(): string
    {
        return Str::random(48);
    }

    public static function newReference(): string
    {
        return 'DP-'.strtoupper(Str::random(12));
    }

    public function getAmountLabelAttribute(): string
    {
        return (float) $this->amount <= 0 ? 'Free' : Course::money((float) $this->amount);
    }

    public function getDownloadUrlAttribute(): string
    {
        return route('shop.download', $this->download_token);
    }

    public function getFirstNameAttribute(): string
    {
        $name = trim((string) $this->buyer_name);

        return explode(' ', $name)[0] ?: $name;
    }
}
