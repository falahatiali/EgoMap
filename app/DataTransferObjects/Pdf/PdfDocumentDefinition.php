<?php

namespace App\DataTransferObjects\Pdf;

readonly class PdfDocumentDefinition
{
    /**
     * @param  list<array<string, mixed>>  $sections
     */
    public function __construct(
        public string $locale,
        public string $filename,
        public PdfTheme $theme,
        public PdfMeta $meta,
        public array $sections,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        /** @var list<array<string, mixed>> $sections */
        $sections = $data['sections'] ?? [];

        return new self(
            locale: (string) ($data['locale'] ?? config('app.locale')),
            filename: (string) ($data['filename'] ?? 'document.pdf'),
            theme: PdfTheme::fromArray(is_array($data['theme'] ?? null) ? $data['theme'] : []),
            meta: PdfMeta::fromArray(is_array($data['meta'] ?? null) ? $data['meta'] : []),
            sections: $sections,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'locale' => $this->locale,
            'filename' => $this->filename,
            'theme' => $this->theme->toArray(),
            'meta' => $this->meta->toArray(),
            'sections' => $this->sections,
        ];
    }
}
