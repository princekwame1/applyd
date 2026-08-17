<?php

namespace App\Support;

/**
 * The floating "chat with us" button on the public site.
 *
 * The number is CMS-editable, so it arrives however whoever typed it felt like
 * writing it — `0240835458`, `+233 24 083 5458`, `024-083-5458`. wa.me accepts
 * exactly one of those shapes: digits only, in full international form, with no
 * plus sign. Normalising here means the admin never has to know that.
 */
class Whatsapp
{
    /** Default calling code for a number typed in local form. */
    public const DEFAULT_DIAL_CODE = '233';

    public static function number(): string
    {
        $stored = Cms::stored('site', 'whatsapp_number');

        // Clearing the field in the CMS is the off switch, so a saved blank
        // must not fall back to the seeded default the way cms() would.
        return static::normalise($stored ?? Cms::configDefault('site', 'whatsapp_number'));
    }

    public static function enabled(): bool
    {
        return static::number() !== '';
    }

    /** The wa.me link, with the greeting pre-typed into the chat. */
    public static function link(): string
    {
        $number = static::number();

        if ($number === '') {
            return '';
        }

        $message = trim(cms('site', 'whatsapp_message'));

        return 'https://wa.me/'.$number.($message !== '' ? '?text='.rawurlencode($message) : '');
    }

    /**
     * Digits only, in international form. A leading `0` is the local-trunk
     * prefix — dropped and replaced with the calling code, because `wa.me/024…`
     * reaches nobody.
     */
    public static function normalise(?string $raw): string
    {
        $digits = preg_replace('/\D+/', '', (string) $raw);

        if ($digits === '') {
            return '';
        }

        // 00233… — the other way of writing a leading +.
        if (str_starts_with($digits, '00')) {
            return substr($digits, 2);
        }

        if (str_starts_with($digits, '0')) {
            return static::DEFAULT_DIAL_CODE.ltrim($digits, '0');
        }

        // Already carries its country code, or was typed with a +.
        return $digits;
    }
}
