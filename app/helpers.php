<?php

use App\Support\Cms;

if (! function_exists('cms')) {
    /** Editable CMS text: stored override, else registry default. */
    function cms(string $page, string $key, ?string $default = null): string
    {
        return Cms::get($page, $key, $default);
    }
}

if (! function_exists('cms_html')) {
    /**
     * Editable CMS rich text for output with {!! !!}.
     * - Plain-text defaults are escaped + line-broken.
     * - A single wrapping <p> is unwrapped so formatting flows inline
     *   inside the page's existing element (no invalid <p> nesting).
     */
    function cms_html(string $page, string $key, ?string $default = null): string
    {
        $value = trim(Cms::get($page, $key, $default));

        if ($value === '') {
            return '';
        }

        if (! str_contains($value, '<')) {
            return nl2br(e($value));
        }

        if (preg_match('/^<p>(.*)<\/p>$/s', $value, $m) && ! str_contains($m[1], '<p')) {
            return $m[1];
        }

        return $value;
    }
}

if (! function_exists('cms_image')) {
    /** Editable CMS image URL. */
    function cms_image(string $page, string $key, ?string $default = null): ?string
    {
        return Cms::image($page, $key, $default);
    }
}
