<?php

use ArPHP\I18N\Arabic;

if (! function_exists('pdf_ar')) {
    /**
     * Convert Arabic/mixed text into shaped presentation forms suitable for
     * dompdf's CPDF backend, which cannot shape or re-order RTL text itself.
     *
     * Works regardless of the current locale: any string that actually
     * contains Arabic characters is shaped. This covers Arabic UI labels
     * (Arabic locale) as well as Arabic stored in the database (e.g. item,
     * section, root-cause or branch names) even when the app is in English.
     */
    function pdf_ar(?string $text): string
    {
        $text = (string) $text;

        if (! preg_match('/[\x{0600}-\x{06FF}]/u', $text)) {
            return $text;
        }

        static $glyphs = null;
        $glyphs ??= new Arabic('Glyphs');

        return (string) $glyphs->utf8Glyphs($text);
    }
}
