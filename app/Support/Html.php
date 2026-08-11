<?php

namespace App\Support;

use HTMLPurifier;
use HTMLPurifier_Config;

class Html
{
    /**
     * Sanitize rich-text HTML coming from the Quill editor down to a safe
     * allow-list of tags. Returns null for empty/blank input.
     */
    public static function clean(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $html = trim($html);

        // Quill leaves an empty paragraph when the editor is cleared. An image
        // carries no text, so it has to be exempted or an image-only body would
        // be thrown away here.
        if ($html === '' || $html === '<p><br></p>'
            || (strip_tags($html) === '' && ! str_contains($html, '<br') && ! str_contains($html, '<img'))) {
            return null;
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,br,strong,b,em,i,u,ol,ul,li,a[href|title|target|rel],h2,h3,blockquote,img[src|alt|width|height]');
        // Only http(s) — blocks `data:` base64 blobs (stripped by mail clients
        // and huge to store) and `javascript:` alike.
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        // An <img> is meaningful even though it has no text content.
        $config->set('AutoFormat.RemoveEmpty.Predicate', ['img' => ['src'], 'colgroup' => [], 'th' => [], 'td' => [], 'iframe' => ['src']]);
        $config->set('AutoFormat.RemoveEmpty', true);
        $config->set('HTML.TargetBlank', true);
        // Rebuild definitions in-memory so no writable cache directory is required.
        $config->set('Cache.DefinitionImpl', null);

        $clean = trim((new HTMLPurifier($config))->purify($html));

        return $clean === '' ? null : $clean;
    }
}
