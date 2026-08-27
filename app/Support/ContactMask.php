<?php

namespace App\Support;

/**
 * Strips contact routes out of candidate-authored free text.
 *
 * The talent pool's promise is that a company sees enough to judge somebody and
 * nothing that lets it reach them until a credit is spent. Masking the `email`
 * and `phone` columns delivers that only for the fields we control — `headline`
 * and `summary` are typed by the candidate and are shown before payment, so
 * "Backend dev, call me on 0244 123 456" hands the details over anyway. Worse,
 * it is the obvious way to *deliberately* route around the paywall, from the
 * side that has every reason to.
 *
 * So the rule is applied to the text as well: anything that could be dialled,
 * mailed or opened is replaced before a locked card renders. This is only ever
 * used on the locked view — an unlocked company has paid and sees the original.
 */
class ContactMask
{
    /** What a redacted run is replaced with. Reads as deliberate, not broken. */
    public const PLACEHOLDER = '[hidden]';

    /**
     * Shortest run of digits treated as a phone number. Seven keeps local
     * numbers in scope while leaving "4 years", "2019" and "Top 100" alone —
     * years of experience and dates are the whole point of a headline.
     */
    private const MIN_PHONE_DIGITS = 7;

    public static function scrub(?string $text): string
    {
        $text = trim((string) $text);

        if ($text === '') {
            return '';
        }

        foreach (static::patterns() as $pattern) {
            $text = preg_replace($pattern, static::PLACEHOLDER, $text) ?? $text;
        }

        // Collapse the whitespace a removed run leaves behind.
        return trim(preg_replace('/\s{2,}/u', ' ', $text) ?? $text);
    }

    /** True if scrubbing would change the text — i.e. it carried a contact route. */
    public static function carriesContact(?string $text): bool
    {
        return static::scrub($text) !== trim((string) $text);
    }

    /**
     * Order matters: emails and links go first, so their digits and dots are
     * already gone by the time the phone rule runs and cannot be re-matched.
     *
     * @return array<int, string>
     */
    private static function patterns(): array
    {
        $min = self::MIN_PHONE_DIGITS;

        return [
            // Email: a literal @, or the bracketed "(at)" dodge.
            '/[\p{L}0-9._%+-]+\s*(?:@|\(\s*at\s*\)|\[\s*at\s*\])\s*[\p{L}0-9.-]+\s*(?:\.|\s+dot\s+)\s*[a-z]{2,}/iu',

            // The fully spelled-out "kwame at gmail dot com". Both halves must
            // be spelled for this to fire: a bare " at " on its own is ordinary
            // English ("Portfolio at kwame.dev") and matching it redacts prose.
            // The domain in that example is caught by the link rule below anyway.
            '/[\p{L}0-9._%+-]+\s+at\s+[\p{L}0-9-]+\s+dot\s+[a-z]{2,}/iu',

            // Anything clickable: full URLs, bare domains, wa.me, linkedin.com/in/…
            '/\b(?:https?:\/\/|www\.)\S+/iu',
            '/\b[\p{L}0-9-]+\.(?:com|net|org|io|co|me|dev|ly|gh|uk|info|biz)(?:\/\S*)?/iu',

            // Social handles.
            '/(?<![\p{L}0-9])@[\p{L}0-9._]{2,}/u',

            // Phone numbers: a long enough digit run, however it is spaced,
            // bracketed or dashed, with an optional leading +.
            '/\+?\d(?:[\s().-]*\d){'.($min - 1).',}/u',
        ];
    }
}
