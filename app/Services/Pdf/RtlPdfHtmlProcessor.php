<?php

namespace App\Services\Pdf;

use App\Support\LocaleConfig;
use ArPHP\I18N\Arabic;

/**
 * Reshapes Arabic/Persian text in HTML so Dompdf renders connected glyphs correctly.
 */
final class RtlPdfHtmlProcessor
{
    private readonly Arabic $arabic;

    public function __construct()
    {
        $this->arabic = new Arabic;
    }

    public function process(string $html, string $locale): string
    {
        if (! LocaleConfig::isRtl($locale)) {
            return $html;
        }

        $positions = $this->arabic->arIdentify($html, true);

        for ($i = count($positions) - 1; $i >= 0; $i -= 2) {
            $start = $positions[$i - 1];
            $length = $positions[$i] - $start;
            $segment = substr($html, $start, $length);
            $reshaped = $this->arabic->utf8Glyphs($segment, 500, true, false);
            $html = substr_replace($html, $reshaped, $start, $length);
        }

        return $html;
    }
}
