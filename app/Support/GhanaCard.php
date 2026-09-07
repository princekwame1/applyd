<?php

namespace App\Support;

/**
 * The Ghana Card number a job poster identifies themselves with.
 *
 * Format is GHA-XXXXXXXXX-X: the prefix, nine digits, then a single check
 * digit. People type it every which way — lower case, spaces instead of
 * dashes, no prefix at all because the card face groups the digits — so the
 * input is normalised to one canonical shape before it is stored. Two records
 * of the same card must never fail to match each other over punctuation, or
 * the duplicate check that makes this worth collecting is worthless.
 */
class GhanaCard
{
    /** The shape everything is stored in. */
    public const PATTERN = '/^GHA-\d{9}-\d$/';

    /**
     * Turn whatever was typed into GHA-XXXXXXXXX-X, or null if it cannot be
     * read as a card number at all. Never throws — validation reports the
     * problem, this only tidies.
     */
    public static function normalise(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        // Strip everything that isn't a digit; the prefix and separators are
        // reconstructed, so "gha 123456789 0" and "1234567890" both land in
        // the same place.
        $digits = preg_replace('/\D+/', '', $value);

        if (strlen((string) $digits) !== 10) {
            return null;
        }

        return sprintf('GHA-%s-%s', substr($digits, 0, 9), substr($digits, 9, 1));
    }

    /** Whether a value can be read as a card number. */
    public static function valid(?string $value): bool
    {
        return static::normalise($value) !== null;
    }

    /**
     * Validation rules for a form field. The closure normalises first so a
     * correctly-numbered card is never rejected over its punctuation.
     */
    public static function rules(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'max:30',
            function (string $attribute, mixed $value, callable $fail) {
                if ($value !== null && trim((string) $value) !== '' && ! static::valid($value)) {
                    $fail('Enter the Ghana Card number as it appears on the card, e.g. GHA-123456789-0.');
                }
            },
        ];
    }

    /** How it is shown when there is nothing on file. */
    public static function display(?string $value): string
    {
        return $value ?: 'Not provided';
    }
}
