<?php

namespace App\Support;

use App\Models\Course;

/**
 * Passing the Paystack transaction charge on to the person paying.
 *
 * The part that trips people up: you cannot just add the fee to the bill.
 * Paystack takes its cut of whatever is *charged*, so billing `price + fee`
 * means it takes a percentage of that larger number and the academy still
 * lands short. The bill has to be grossed up instead —
 *
 *     total = (net + fixed) / (1 - rate)
 *
 * — which is the amount whose fee, once deducted, leaves exactly `net`.
 *
 * Rates live in config because they are Paystack's to change and differ by
 * country and payment method. Check yours on the Paystack dashboard rather
 * than trusting the default here.
 */
class PaystackFees
{
    /** Are we passing the charge on at all? Off means the academy absorbs it. */
    public static function passedOn(): bool
    {
        return (bool) config('services.paystack.fee.pass_on', false);
    }

    public static function rate(): float
    {
        return (float) config('services.paystack.fee.percent', 0) / 100;
    }

    public static function fixed(): float
    {
        return (float) config('services.paystack.fee.fixed', 0);
    }

    /** Paystack caps its fee on large transactions; null means no cap. */
    public static function cap(): ?float
    {
        $cap = config('services.paystack.fee.cap');

        return $cap === null || $cap === '' ? null : (float) $cap;
    }

    /**
     * What to charge so that `$net` actually reaches us.
     *
     * Rounded **up** to the pesewa: rounding down would leave the academy a
     * fraction short on every single transaction, and the payer a fraction is
     * not worth a line of arithmetic to anyone.
     */
    public static function gross(float $net): float
    {
        if (! static::passedOn() || $net <= 0) {
            return round($net, 2);
        }

        $rate = static::rate();

        // A rate of 100% or more can't be grossed up — there is no charge whose
        // fee leaves anything behind. Treat it as misconfiguration and bill the
        // net rather than dividing by zero or going negative.
        if ($rate >= 1) {
            return round($net, 2);
        }

        $total = static::ceilPesewa(($net + static::fixed()) / (1 - $rate));

        $cap = static::cap();

        if ($cap !== null && $total - $net > $cap) {
            $total = round($net + $cap, 2);
        }

        return $total;
    }

    /** The charge itself — what the breakdown on a page shows as a line. */
    public static function fee(float $net): float
    {
        return round(static::gross($net) - round($net, 2), 2);
    }

    /**
     * The net hiding inside an amount that was already grossed up. Used when
     * reading a payment back: what the customer paid includes the fee, and it
     * is the net that belongs against a balance.
     *
     * Only a fallback — the base amount travels in the transaction metadata,
     * because inverting a capped or rounded figure can land a pesewa out.
     */
    public static function netFrom(float $gross): float
    {
        if (! static::passedOn() || $gross <= 0) {
            return round($gross, 2);
        }

        $rate = static::rate();

        if ($rate >= 1) {
            return round($gross, 2);
        }

        $net = $gross * (1 - $rate) - static::fixed();

        $cap = static::cap();

        if ($cap !== null && $gross - $net > $cap) {
            $net = $gross - $cap;
        }

        return round(max($net, 0), 2);
    }

    /** Amount in pesewas, which is what the Paystack API wants. */
    public static function pesewas(float $amount): int
    {
        return (int) round($amount * 100);
    }

    /** How the charge reads on a page, e.g. "1.95% + GHS 0.00". */
    public static function label(): string
    {
        $parts = [];

        if (config('services.paystack.fee.percent')) {
            $parts[] = rtrim(rtrim(number_format((float) config('services.paystack.fee.percent'), 2), '0'), '.').'%';
        }

        if (static::fixed() > 0) {
            $parts[] = Course::money(static::fixed());
        }

        return implode(' + ', $parts) ?: 'transaction charge';
    }

    /** Round up to whole pesewas. */
    protected static function ceilPesewa(float $amount): float
    {
        return ceil(round($amount * 100, 4)) / 100;
    }
}
