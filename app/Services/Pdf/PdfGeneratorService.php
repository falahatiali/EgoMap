<?php

namespace App\Services\Pdf;

use App\DataTransferObjects\Pdf\PdfDocumentDefinition;
use App\DataTransferObjects\Pdf\PdfTheme;
use App\Support\LocaleConfig;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Spatie\LaravelPdf\Facades\Pdf;

class PdfGeneratorService
{
    public function generate(PdfDocumentDefinition $document): string
    {
        $document = $this->ensureCompleteDocument($document);

        $previousLocale = App::getLocale();
        App::setLocale($document->locale);

        try {
            $directory = storage_path('app/pdf/temp');

            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            $path = $directory.'/'.Str::uuid()->toString().'.pdf';

            $direction = LocaleConfig::isRtl($document->locale) ? 'rtl' : 'ltr';

            $html = View::make('pdf.document', [
                'document' => $document,
                'direction' => $direction,
            ])->render();

            if ($direction === 'rtl') {
                $html = app(RtlPdfHtmlProcessor::class)->process($html, $document->locale);
            }

            Pdf::driver(config('laravel-pdf.driver', 'dompdf'))
                ->html($html)
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

    private function ensureCompleteDocument(PdfDocumentDefinition $document): PdfDocumentDefinition
    {
        $theme = $document->theme;

        $normalizedTheme = PdfTheme::fromArray([
            'accent' => $theme->accent,
            'accent_soft' => $theme->accentSoft,
            'accent_dark' => $theme->accentDark,
            'background' => $this->themeValue($theme, 'background', '#f4f3ff'),
            'surface' => $this->themeValue($theme, 'surface', '#ffffff'),
            'text' => $this->themeValue($theme, 'text', '#0f172a'),
            'text_muted' => $this->themeValue($theme, 'textMuted', '#64748b'),
            'border' => $this->themeValue($theme, 'border', '#e2e8f0'),
            'group_background' => $this->themeValue($theme, 'groupBackground', '#f8f7ff'),
            'group_label' => $this->themeValue($theme, 'groupLabel', ''),
        ]);

        return new PdfDocumentDefinition(
            locale: $document->locale,
            filename: $document->filename,
            theme: $normalizedTheme,
            meta: $document->meta,
            sections: $document->sections,
        );
    }

    private function themeValue(object $theme, string $property, string $default): string
    {
        if (! property_exists($theme, $property)) {
            return $default;
        }

        return (string) $theme->{$property};
    }
}
