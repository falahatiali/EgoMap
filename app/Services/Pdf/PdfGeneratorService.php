<?php

namespace App\Services\Pdf;

use App\DataTransferObjects\Pdf\PdfDocumentDefinition;
use App\Support\LocaleConfig;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Spatie\LaravelPdf\Facades\Pdf;

class PdfGeneratorService
{
    public function generate(PdfDocumentDefinition $document): string
    {
        $previousLocale = App::getLocale();
        App::setLocale($document->locale);

        try {
            $directory = storage_path('app/pdf/temp');

            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            $path = $directory.'/'.Str::uuid()->toString().'.pdf';

            Pdf::driver(config('laravel-pdf.driver', 'dompdf'))
                ->view('pdf.document', [
                    'document' => $document,
                    'direction' => LocaleConfig::isRtl($document->locale) ? 'rtl' : 'ltr',
                ])
                ->format('a4')
                ->margins(10, 12, 14, 12)
                ->meta(
                    title: $document->meta->title,
                    author: $document->meta->brand,
                    subject: $document->meta->subtitle,
                )
                ->name($document->filename)
                ->save($path);

            return $path;
        } finally {
            App::setLocale($previousLocale);
        }
    }

    public function delete(string $path): void
    {
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
