<?php

namespace App\DataTransferObjects\Pdf;

readonly class PdfTheme
{
    public function __construct(
        public string $accent,
        public string $accentSoft,
        public string $accentDark,
        public string $background = '#f4f3ff',
        public string $surface = '#ffffff',
        public string $text = '#0f172a',
        public string $textMuted = '#64748b',
        public string $border = '#e2e8f0',
        public string $groupBackground = '#f8f7ff',
        public string $groupLabel = '',
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            accent: (string) ($data['accent'] ?? '#6366f1'),
            accentSoft: (string) ($data['accent_soft'] ?? '#eef2ff'),
            accentDark: (string) ($data['accent_dark'] ?? '#4f46e5'),
            background: (string) ($data['background'] ?? '#f4f3ff'),
            surface: (string) ($data['surface'] ?? '#ffffff'),
            text: (string) ($data['text'] ?? '#0f172a'),
            textMuted: (string) ($data['text_muted'] ?? '#64748b'),
            border: (string) ($data['border'] ?? '#e2e8f0'),
            groupBackground: (string) ($data['group_background'] ?? '#f8f7ff'),
            groupLabel: (string) ($data['group_label'] ?? ''),
        );
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'accent' => $this->accent,
            'accent_soft' => $this->accentSoft,
            'accent_dark' => $this->accentDark,
            'background' => $this->background,
            'surface' => $this->surface,
            'text' => $this->text,
            'text_muted' => $this->textMuted,
            'border' => $this->border,
            'group_background' => $this->groupBackground,
            'group_label' => $this->groupLabel,
        ];
    }
}
