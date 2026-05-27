<?php

namespace App\Services\Pdf;

use Dompdf\Dompdf;
use Dompdf\Options;

final class DompdfFontInstaller
{
    public static function ensureVazirmatnInstalled(): void
    {
        $fontDir = (string) config('laravel-pdf.dompdf.font_dir');

        if (! is_dir($fontDir)) {
            mkdir($fontDir, 0755, true);
        }

        $cacheFile = $fontDir.'/installed-fonts.json';

        if (is_readable($cacheFile)) {
            $families = json_decode((string) file_get_contents($cacheFile), true);

            if (is_array($families) && isset($families['vazirmatn'])) {
                return;
            }
        }

        $tempDir = storage_path('app/pdf/temp');

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $options = new Options;
        $options->setChroot(base_path());
        $options->setFontDir($fontDir);
        $options->setFontCache($fontDir);
        $options->setTempDir($tempDir);

        $dompdf = new Dompdf($options);
        $metrics = $dompdf->getFontMetrics();

        $regular = resource_path('fonts/pdf/Vazirmatn-Regular.ttf');
        $bold = resource_path('fonts/pdf/Vazirmatn-Bold.ttf');

        $metrics->registerFont(
            ['family' => 'vazirmatn', 'style' => 'normal', 'weight' => 'normal'],
            'file://'.$regular,
        );

        $metrics->registerFont(
            ['family' => 'vazirmatn', 'style' => 'normal', 'weight' => 'bold'],
            'file://'.$bold,
        );
    }
}
