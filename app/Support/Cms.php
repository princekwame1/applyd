<?php

namespace App\Support;

use App\Models\PageContent;
use Illuminate\Support\Str;

class Cms
{
    protected static ?array $store = null;

    /** Load all stored overrides once per request: [page][key] => value */
    protected static function store(): array
    {
        if (self::$store === null) {
            self::$store = [];
            try {
                foreach (PageContent::all() as $row) {
                    self::$store[$row->page][$row->key] = $row->value;
                }
            } catch (\Throwable $e) {
                // Table not migrated yet — fall back to config defaults.
            }
        }

        return self::$store;
    }

    public static function flush(): void
    {
        self::$store = null;
    }

    /** Registry default for a field (defined in config/cms.php). */
    public static function configDefault(string $page, string $key): ?string
    {
        foreach (config("cms.pages.$page.sections", []) as $section) {
            if (isset($section['fields'][$key])) {
                return $section['fields'][$key]['default'] ?? null;
            }
        }

        return null;
    }

    public static function fieldMeta(string $page, string $key): ?array
    {
        foreach (config("cms.pages.$page.sections", []) as $section) {
            if (isset($section['fields'][$key])) {
                return $section['fields'][$key];
            }
        }

        return null;
    }

    /**
     * The stored override exactly as saved, or null when there isn't one.
     *
     * Unlike get(), a saved blank stays blank rather than falling back to the
     * registry default — which is what lets an admin turn something off by
     * clearing its field. Only reach for this where "cleared" has to mean
     * something different from "never touched".
     */
    public static function stored(string $page, string $key): ?string
    {
        return self::store()[$page][$key] ?? null;
    }

    /** Get a text value: stored override, else registry default, else $default. */
    public static function get(string $page, string $key, ?string $default = null): string
    {
        $value = self::store()[$page][$key] ?? null;

        if ($value !== null && $value !== '') {
            return $value;
        }

        return (string) ($default ?? self::configDefault($page, $key) ?? '');
    }

    /** Resolve an image field to a URL (stored path -> asset, else the default). */
    public static function image(string $page, string $key, ?string $default = null): ?string
    {
        $value = self::store()[$page][$key] ?? null;

        if ($value) {
            return Str::startsWith($value, ['http://', 'https://', '/']) ? $value : asset('storage/'.$value);
        }

        $configDefault = self::configDefault($page, $key);

        return $default ?? ($configDefault ? asset($configDefault) : null);
    }
}
