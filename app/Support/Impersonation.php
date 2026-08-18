<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Signing in as somebody else to see what they see.
 *
 * The whole feature is one session key: `impersonator_id` holds whoever started
 * it, and the logged-in user is the person being looked at. Everything else —
 * the banner, the stop button, who is allowed to do it — reads from that.
 *
 * Two rules do the real work. **You can never impersonate upwards**: an admin
 * taking over a super account would be a way to grant yourself the one
 * permission you were not given, so seniority is checked both ways rather than
 * just "is an admin". And **the original account is never touched** — no
 * password, no role, nothing is written to it — so an impersonation that is
 * abandoned, or a session that simply expires, leaves nothing behind.
 */
class Impersonation
{
    public const SESSION_KEY = 'impersonator_id';

    /** Roles nobody may impersonate unless they hold the same rank. */
    private const SENIOR_ROLES = ['super', 'admin'];

    public static function active(): bool
    {
        return session()->has(self::SESSION_KEY);
    }

    /** The admin behind the curtain, or null when nobody is impersonating. */
    public static function impersonator(): ?User
    {
        $id = session(self::SESSION_KEY);

        return $id ? User::find($id) : null;
    }

    public static function canImpersonate(?User $actor): bool
    {
        // Already pretending to be someone: a chain would make "who did this?"
        // unanswerable, and stopping would only get you halfway back.
        return $actor !== null
            && ! self::active()
            && $actor->hasAnyRole(['admin', 'super']);
    }

    /**
     * Why $actor may not become $target, or null when they may.
     *
     * Returned as a sentence rather than a boolean because every refusal here
     * is shown to the person who tried.
     */
    public static function blockedReason(?User $actor, User $target): ?string
    {
        if (! self::canImpersonate($actor)) {
            return self::active()
                ? 'Stop the current impersonation first.'
                : 'You are not allowed to impersonate.';
        }

        if ($actor->id === $target->id) {
            return 'You are already yourself.';
        }

        // Only a super may take over another senior account. Otherwise an admin
        // could sign in as a super and hand themselves anything.
        if ($target->hasAnyRole(self::SENIOR_ROLES) && ! $actor->hasRole('super')) {
            return $target->name.' is an administrator — only a super can impersonate them.';
        }

        return null;
    }

    /**
     * Become $target. Returns where to send them next: whichever home that
     * account actually has in this app.
     */
    public static function start(User $actor, User $target): string
    {
        Log::info('Impersonation started', [
            'impersonator_id' => $actor->id,
            'impersonator' => $actor->email,
            'target_id' => $target->id,
            'target' => $target->email,
        ]);

        Auth::login($target);

        // Written after the login: Auth::login regenerates nothing by itself,
        // but putting it second makes the order explicit — the key must survive
        // into the impersonated session or there is no way back.
        session()->put(self::SESSION_KEY, $actor->id);

        return self::homeFor($target);
    }

    /** Hand the session back. Safe to call when nothing is being impersonated. */
    public static function stop(): string
    {
        $actor = self::impersonator();

        session()->forget(self::SESSION_KEY);

        if (! $actor) {
            return route('login');
        }

        Log::info('Impersonation stopped', [
            'impersonator_id' => $actor->id,
            'was' => Auth::id(),
        ]);

        Auth::login($actor);

        return self::homeFor($actor);
    }

    /**
     * Where an account belongs in *this* app. A student's dashboard is the
     * separate portal, which has its own session and cannot be entered from
     * here — so they land on the public site, and the banner says as much.
     */
    public static function homeFor(User $user): string
    {
        if ($user->hasRole('company')) {
            return route('company.home');
        }

        if ($user->hasAnyRole(['admin', 'super'])) {
            return route('dashboard');
        }

        return route('landing');
    }
}
