<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Something the academy sells as a download.
 *
 * Delivery is either a file we hold or a link we send — `deliversFile()` and
 * `deliversLink()` are the only two ways to ask, and the form makes sure one
 * of them is always true.
 */
class DigitalProduct extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'tagline',
        'description',
        'category',
        'format',
        'price',
        'cover',
        'file_path',
        'file_name',
        'file_size',
        'external_url',
        'is_published',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'file_size' => 'integer',
        'is_published' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (DigitalProduct $product) {
            if (empty($product->slug) && $product->title) {
                $product->slug = static::uniqueSlug($product->title, $product->id);
            }
        });
    }

    public static function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'product';
        $slug = $base;
        $i = 2;

        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(ProductOrder::class);
    }

    /**
     * Checkouts that were never settled — a row is minted the moment somebody
     * opens Paystack, so a product nobody bought can still be pointed at by
     * the people who walked away. They carry no money and their token opens
     * nothing, so they go with the product; a *paid* order is what stops the
     * delete happening at all (`DigitalProductController::blockedReason`).
     */
    public function purgeUnsettledOrders(): void
    {
        $this->orders()->where('status', '!=', 'paid')->delete();
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /** Free is a real price, not a missing one — a lead magnet skips Paystack. */
    public function isFree(): bool
    {
        return (float) $this->price <= 0;
    }

    public function deliversFile(): bool
    {
        return (bool) $this->file_path;
    }

    public function deliversLink(): bool
    {
        return ! $this->deliversFile() && (bool) $this->external_url;
    }

    public function getPriceLabelAttribute(): string
    {
        return $this->isFree() ? 'Free' : Course::money((float) $this->price);
    }

    public function getCoverUrlAttribute(): ?string
    {
        return $this->cover ? asset('storage/'.$this->cover) : null;
    }

    /** How big the download is, in the units a buyer thinks in. */
    public function getFileSizeLabelAttribute(): ?string
    {
        if (! $this->file_size) {
            return null;
        }

        $mb = $this->file_size / 1048576;

        return $mb >= 1
            ? number_format($mb, 1).' MB'
            : max(1, (int) round($this->file_size / 1024)).' KB';
    }

    public function getShortDescriptionAttribute(): string
    {
        return Str::limit(trim(strip_tags((string) $this->description)), 130);
    }
}
