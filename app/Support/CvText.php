<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Best-effort plain text out of an uploaded CV.
 *
 * Two things use it: keyword scoring (App\Support\FitScore) and the in-app
 * viewer, which has nothing to show a recruiter for a Word file otherwise.
 *
 * Best-effort is the whole design. A scanned PDF is a picture of a CV and
 * holds no text at all; an old binary .doc is not worth guessing at. So this
 * returns **null** rather than something half-read, and every caller treats
 * null as "could not check" — never as "matched nothing". A keyword scored
 * zero because the file would not open is a candidate quietly buried, which
 * is far worse than an unscored one the recruiter is told to read.
 */
class CvText
{
    /** Enough text to be worth trusting; below this it is noise. */
    private const MIN_LETTERS = 30;

    /** Kept out of the column, and out of any single scoring pass. */
    public const MAX_CHARS = 200000;

    public static function fromStorage(string $path, string $disk = 'local'): ?string
    {
        $storage = Storage::disk($disk);

        if (! $path || ! $storage->exists($path)) {
            return null;
        }

        return static::fromString($storage->get($path), $path);
    }

    /** @param string $name anything ending in the real extension */
    public static function fromString(string $binary, string $name): ?string
    {
        $text = match (strtolower(pathinfo($name, PATHINFO_EXTENSION))) {
            'txt' => $binary,
            'docx' => static::fromDocx($binary),
            'pdf' => static::fromPdf($binary),
            // .doc is a binary Word stream. Guessing at it produces noise,
            // and noise scores worse than an honest "not read".
            default => null,
        };

        return static::tidy($text);
    }

    /** A .docx is a zip; the body is one XML entry inside it. */
    private static function fromDocx(string $binary): ?string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'cv');

        if ($tmp === false) {
            return null;
        }

        try {
            file_put_contents($tmp, $binary);

            $zip = new ZipArchive;

            if ($zip->open($tmp) !== true) {
                return null;
            }

            $xml = $zip->getFromName('word/document.xml');
            $zip->close();

            if ($xml === false) {
                return null;
            }

            // Paragraphs and breaks become newlines before the tags go, so
            // words either side of them don't run together.
            $xml = preg_replace('#</w:p>|<w:br/>|<w:tab/>#', ' ', $xml);

            return html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
        } finally {
            @unlink($tmp);
        }
    }

    /**
     * Text-bearing PDFs keep their words in compressed content streams as
     * parenthesised strings. That is all we take: every `(...)` in every
     * stream we can inflate, joined. It is crude — but it is only ever fed
     * to a case-insensitive keyword match, never shown as the document.
     */
    private static function fromPdf(string $binary): ?string
    {
        if (! preg_match_all('/stream\r?\n(.*?)endstream/s', $binary, $matches)) {
            return null;
        }

        $text = '';

        foreach ($matches[1] as $stream) {
            $data = @gzuncompress($stream);

            if ($data === false) {
                $data = @gzinflate($stream);
            }

            // An uncompressed content stream is readable as it stands.
            if ($data === false) {
                $data = str_contains($stream, 'Tj') || str_contains($stream, 'TJ') ? $stream : false;
            }

            if ($data === false) {
                continue;
            }

            $text .= static::stringsIn($data);

            if (strlen($text) > self::MAX_CHARS) {
                break;
            }
        }

        return $text;
    }

    /** Every literal string in a PDF content stream, unescaped. */
    private static function stringsIn(string $data): string
    {
        if (! preg_match_all('/\((?:\\\\.|[^\\\\()])*\)/s', $data, $found)) {
            return '';
        }

        $out = '';

        foreach ($found[0] as $literal) {
            $out .= static::unescapePdf(substr($literal, 1, -1)).' ';
        }

        return $out;
    }

    private static function unescapePdf(string $value): string
    {
        return preg_replace_callback('/\\\\(n|r|t|b|f|\(|\)|\\\\|[0-7]{1,3})/', function ($m) {
            return match ($m[1]) {
                'n' => "\n",
                'r' => "\r",
                't' => "\t",
                'b', 'f' => ' ',
                '(' => '(',
                ')' => ')',
                '\\' => '\\',
                default => chr(octdec($m[1])),
            };
        }, $value);
    }

    /** Collapse the whitespace, cap the length, and refuse what isn't text. */
    private static function tidy(?string $text): ?string
    {
        if ($text === null || $text === '') {
            return null;
        }

        // Anything not valid UTF-8 is binary that slipped through.
        if (! mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        }

        $text = preg_replace('/[^\P{C}\n]+/u', ' ', $text) ?? '';
        $text = trim(preg_replace('/[ \t]*\n[ \t\n]*/u', "\n", preg_replace('/[ \t]+/u', ' ', $text)) ?? '');

        if (preg_match_all('/\p{L}/u', $text) < self::MIN_LETTERS) {
            return null;
        }

        return mb_substr($text, 0, self::MAX_CHARS);
    }
}
