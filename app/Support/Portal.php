<?php

namespace App\Support;

/**
 * Links into the sibling learning portal (applyd-portal), which runs as its own
 * app on the same database and shares these accounts.
 *
 * Students sign in *there*, never here — this site's /login opens onto a
 * `role:admin|super` group a student cannot enter. So a student link must never
 * be allowed to come out as an applydacademy.com address.
 *
 * That is why the portal address is a constant with `PORTAL_URL` as an
 * override, rather than an env var with a fallback. The earlier shape fell back
 * to `route('login')` when the setting was missing, which is exactly what a
 * fresh deploy looks like: the links kept working, kept looking plausible, and
 * sent every student to a door that wouldn't open.
 */
class Portal
{
    /** Where the student dashboard actually lives. */
    public const DEFAULT_URL = 'https://sts.applydacademy.com';

    /** The portal root, no trailing slash. Always a portal address. */
    public static function url(): string
    {
        // Whitespace trimmed as well as the trailing slash: `PORTAL_URL=" "`
        // is a setting nobody meant, and it would otherwise sail through as a
        // non-empty value and produce "   /login".
        $configured = rtrim(trim((string) config('services.portal.url')), '/');

        return $configured !== '' ? $configured : static::DEFAULT_URL;
    }

    /** Where a student signs in. */
    public static function loginUrl(): string
    {
        return static::url().'/login';
    }

    /** Where a student changes the temporary password we issued. */
    public static function passwordUrl(): string
    {
        return static::url().'/profile';
    }

    /**
     * Has someone pointed PORTAL_URL at this site? Then every student link is
     * a dead end, and the dashboard says so rather than letting it be found one
     * bounced login at a time.
     */
    public static function pointsAtThisSite(): bool
    {
        $portal = parse_url(static::url(), PHP_URL_HOST);
        $site = parse_url((string) config('app.url'), PHP_URL_HOST);

        return $portal !== null && $site !== null && strcasecmp($portal, $site) === 0;
    }
}
