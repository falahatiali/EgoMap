<?php

namespace App\DataTransferObjects\Pdf;

readonly class PdfMailEnvelope
{
    public function __construct(
        public string $subject,
        public string $headline,
        public string $intro,
        public ?string $actionUrl = null,
        public ?string $actionLabel = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            subject: (string) ($data['subject'] ?? ''),
            headline: (string) ($data['headline'] ?? ''),
            intro: (string) ($data['intro'] ?? ''),
            actionUrl: isset($data['action_url']) ? (string) $data['action_url'] : null,
            actionLabel: isset($data['action_label']) ? (string) $data['action_label'] : null,
        );
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'subject' => $this->subject,
            'headline' => $this->headline,
            'intro' => $this->intro,
            'action_url' => $this->actionUrl,
            'action_label' => $this->actionLabel,
        ];
    }
}
