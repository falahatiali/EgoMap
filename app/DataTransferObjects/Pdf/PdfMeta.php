<?php

namespace App\DataTransferObjects\Pdf;

readonly class PdfMeta
{
    public function __construct(
        public string $title,
        public string $brand,
        public ?string $subtitle = null,
        public ?string $footerNote = null,
        public ?string $actionUrl = null,
        public ?string $actionLabel = null,
        public ?string $generatedAtLabel = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            title: (string) ($data['title'] ?? ''),
            brand: (string) ($data['brand'] ?? config('app.name')),
            subtitle: isset($data['subtitle']) ? (string) $data['subtitle'] : null,
            footerNote: isset($data['footer_note']) ? (string) $data['footer_note'] : null,
            actionUrl: isset($data['action_url']) ? (string) $data['action_url'] : null,
            actionLabel: isset($data['action_label']) ? (string) $data['action_label'] : null,
            generatedAtLabel: isset($data['generated_at_label']) ? (string) $data['generated_at_label'] : null,
        );
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'brand' => $this->brand,
            'subtitle' => $this->subtitle,
            'footer_note' => $this->footerNote,
            'action_url' => $this->actionUrl,
            'action_label' => $this->actionLabel,
            'generated_at_label' => $this->generatedAtLabel,
        ];
    }
}
