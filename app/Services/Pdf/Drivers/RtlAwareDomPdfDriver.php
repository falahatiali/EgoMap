<?php

namespace App\Services\Pdf\Drivers;

use App\Services\Pdf\DompdfFontInstaller;
use Dompdf\Options;
use Spatie\LaravelPdf\Drivers\DomPdfDriver;

class RtlAwareDomPdfDriver extends DomPdfDriver
{
    protected function buildOptions(): Options
    {
        DompdfFontInstaller::ensureVazirmatnInstalled();

        $options = parent::buildOptions();

        $fontDir = (string) config('laravel-pdf.dompdf.font_dir');
        $tempDir = storage_path('app/pdf/temp');

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $options->setFontDir($fontDir);
        $options->setFontCache($fontDir);
        $options->setTempDir($tempDir);
        $options->setChroot((string) (config('laravel-pdf.dompdf.chroot') ?: base_path()));

        return $options;
    }
}
