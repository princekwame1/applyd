<?php

namespace App\Support;

/**
 * Links into the sibling learning portal (applyd-portal), which runs as its own
 * app on the same database and shares these accounts.
 *
 * Students sign in *there*, not here — this site's /login is the staff door and
 * a `student` role can't get past its `role:admin|super` group. So every
 * credential we hand out has to point at the portal, which is why the base URL
 * is configuration rather than a `route()` call.
 */
class Portal
{
    public static function url(): string
    {
        return rtrim((string) config('services.portal.url'), '/');
    }

    public static function configured(): bool
    {
        return static::url() !== '';
    }

    /** Where a student signs in. Falls back to this site if the portal URL is unset. */
    public static function loginUrl(): string
    {
        return static::configured() ? static::url().'/login' : route('login');
    }

    /** Where a student changes the temporary password we issued. */
    public static function passwordUrl(): string
    {
        return static::configured() ? static::url().'/profile' : route('profile.edit');
    }
}
