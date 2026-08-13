<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SessionVideo extends Model
{
    protected $fillable = [
        'title',
        'youtube_id',
        'session_label',
        'description',
        'recorded_on',
        'thumbnail',
        'is_published',
        'sort_order',
    ];

    protected $casts = [
        'recorded_on' => 'date',
        'is_published' => 'boolean',
    ];

    /**
     * Pull the video id out of anything an admin is likely to paste — a watch
     * link, a share link, an embed/shorts/live URL, or the bare id itself.
     * Returns null when the input isn't a YouTube video, which is what the
     * form validation reports back.
     */
    public static function parseYoutubeId(?string $input): ?string
    {
        $input = trim((string) $input);

        if ($input === '') {
            return null;
        }

        // Bare id: exactly the 11 characters YouTube uses.
        if (preg_match('~^[A-Za-z0-9_-]{11}$~', $input)) {
            return $input;
        }

        $patterns = [
            '~youtube\.com/watch\?(?:.*&)?v=([A-Za-z0-9_-]{11})~i',
            '~youtu\.be/([A-Za-z0-9_-]{11})~i',
            '~youtube\.com/(?:embed|shorts|live|v)/([A-Za-z0-9_-]{11})~i',
            '~youtube-nocookie\.com/embed/([A-Za-z0-9_-]{11})~i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function getWatchUrlAttribute(): string
    {
        return 'https://www.youtube.com/watch?v='.$this->youtube_id;
    }

    /**
     * nocookie host + no related videos from other channels: the iframe is only
     * created after the viewer clicks play, so nothing loads until then.
     */
    public function getEmbedUrlAttribute(): string
    {
        return 'https://www.youtube-nocookie.com/embed/'.$this->youtube_id.'?autoplay=1&rel=0';
    }

    /**
     * A custom upload wins; otherwise YouTube's own still for the video.
     */
    public function getThumbnailUrlAttribute(): string
    {
        if ($this->thumbnail) {
            return asset('storage/'.$this->thumbnail);
        }

        return 'https://i.ytimg.com/vi/'.$this->youtube_id.'/hqdefault.jpg';
    }

    public function getDateLabelAttribute(): ?string
    {
        return $this->recorded_on?->format('M j, Y');
    }

    public function getShortDescriptionAttribute(): string
    {
        return Str::limit((string) $this->description, 120);
    }
}
