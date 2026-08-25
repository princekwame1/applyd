<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * How many emails the host will still accept this hour.
 *
 * cPanel counts messages over a *rolling* sixty minutes, so this keeps the
 * timestamp of every send in the last hour rather than a counter in a fixed
 * bucket: a fixed hourly bucket lets 2x the limit through across a boundary
 * (a full allowance at :59 and another at :01), which is exactly the burst
 * the limit exists to stop.
 *
 * The list is at most `limit` integers — a few kilobytes of cache — and every
 * read-modify-write is taken under a lock, because the whole point is that two
 * workers must not both think they had the last slot.
 */
class MailThrottle
{
    public const KEY = 'mail:sent-window';

    public const LOCK = 'mail:sent-window:lock';

    /** Seconds in the window the host measures. */
    public const WINDOW = 3600;

    public static function limit(): int
    {
        return max(0, (int) config('mail.hourly_limit', 0));
    }

    public static function enabled(): bool
    {
        return static::limit() > 0;
    }

    /**
     * Claim one of this hour's sends. False means the allowance is spent and
     * the caller must wait — never that anything went wrong.
     */
    public static function attempt(): bool
    {
        if (! static::enabled()) {
            return true;
        }

        return static::locked(function () {
            $sends = static::window();

            if (count($sends) >= static::limit()) {
                return false;
            }

            $sends[] = now()->getTimestamp();
            static::store($sends);

            return true;
        });
    }

    /** Sends left before the host starts refusing. */
    public static function remaining(): int
    {
        if (! static::enabled()) {
            return PHP_INT_MAX;
        }

        return max(0, static::limit() - count(static::window()));
    }

    public static function used(): int
    {
        return count(static::window());
    }

    /**
     * Seconds until the next slot frees up — the oldest send in the window
     * falling out of it. Never returns 0 while the allowance is spent, or a
     * released job would come straight back and spin.
     */
    public static function availableIn(): int
    {
        $sends = static::window();

        if (! static::enabled() || count($sends) < static::limit()) {
            return 0;
        }

        return max(1, min($sends) + static::WINDOW - now()->getTimestamp());
    }

    /** Forget the window — for tests and for an admin who has raised the cap. */
    public static function reset(): void
    {
        Cache::forget(static::KEY);
    }

    /** Timestamps of the sends still inside the rolling window. */
    protected static function window(): array
    {
        // now(), not time(): the app's clock is the one everything else uses.
        $cutoff = now()->getTimestamp() - static::WINDOW;

        return array_values(array_filter(
            (array) Cache::get(static::KEY, []),
            fn ($at) => (int) $at > $cutoff,
        ));
    }

    protected static function store(array $sends): void
    {
        Cache::put(static::KEY, $sends, static::WINDOW * 2);
    }

    /**
     * Run the check under a lock. If the lock can't be had we still answer —
     * a stuck lock must slow mail down, not stop it.
     */
    protected static function locked(callable $callback): bool
    {
        try {
            $lock = Cache::lock(static::LOCK, 5);

            return (bool) $lock->block(3, $callback);
        } catch (\Throwable $e) {
            return (bool) $callback();
        }
    }
}
