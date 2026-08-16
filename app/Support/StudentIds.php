<?php

namespace App\Support;

use App\Models\User;

/**
 * Student IDs: an 8-digit code — the four-digit year of admission followed by
 * a running number within that year. 2026's first student is 20260001.
 *
 * Digits only, so it can be read out over the phone, typed on a numeric keypad
 * and printed on a card without anyone wondering about the slashes. The year
 * up front keeps it sortable and tells you at a glance which intake someone
 * came in with — and it is what stops the sequence from ever running into last
 * year's numbers.
 *
 * That leaves 9,999 admissions in a year, which is far beyond anything the
 * academy runs; if it ever isn't, the year prefix is the piece to widen.
 */
class StudentIds
{
    public const LENGTH = 8;

    private const SEQUENCE_DIGITS = 4;

    /**
     * The next free ID for the given year. Uniqueness is enforced by the index
     * on `users.student_id`; the caller retries on collision, so two people
     * finishing registration in the same instant can't be handed one ID.
     */
    public static function next(?int $year = null): string
    {
        $prefix = (string) ($year ?? (int) now()->format('Y'));

        // Highest number issued this year, read off the IDs themselves rather
        // than a counter table — nothing to fall out of step with the real rows.
        $latest = User::where('student_id', 'like', $prefix.'%')
            ->orderByDesc('student_id')
            ->value('student_id');

        $sequence = $latest ? ((int) substr($latest, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $sequence, self::SEQUENCE_DIGITS, '0', STR_PAD_LEFT);
    }

    /** Does this look like an ID we issued? Used to validate what's typed in. */
    public static function looksValid(string $value): bool
    {
        return (bool) preg_match('/^\d{'.self::LENGTH.'}$/', $value);
    }
}
