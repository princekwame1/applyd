<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = [
        'key',
        'subject',
        'heading',
        'body',
        'cta_label',
        'cta_url',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    /**
     * Every template declared in config/email_templates.php.
     */
    public static function definitions(): array
    {
        return config('email_templates.templates', []);
    }

    public static function definition(string $key): ?array
    {
        return config("email_templates.templates.$key");
    }

    /**
     * The config defaults with any admin override layered on top. Returns null
     * for an unknown key so callers can 404 / skip sending.
     */
    public static function resolve(string $key): ?array
    {
        $definition = static::definition($key);

        if (! $definition) {
            return null;
        }

        $override = static::where('key', $key)->first();

        return [
            'key' => $key,
            'label' => $definition['label'] ?? $key,
            'description' => $definition['description'] ?? null,
            'audience' => $definition['audience'] ?? null,
            'subject' => static::pick($override?->subject, $definition['subject'] ?? ''),
            'heading' => static::pick($override?->heading, $definition['heading'] ?? ''),
            'body' => static::pick($override?->body, $definition['body'] ?? ''),
            'cta_label' => static::pick($override?->cta_label, $definition['cta_label'] ?? null),
            'cta_url' => static::pick($override?->cta_url, $definition['cta_url'] ?? null),
            'enabled' => $override?->enabled ?? true,
            'customised' => (bool) $override,
            'updated_at' => $override?->updated_at,
        ];
    }

    protected static function pick(?string $override, ?string $default): ?string
    {
        return ($override !== null && trim($override) !== '') ? $override : $default;
    }
}
