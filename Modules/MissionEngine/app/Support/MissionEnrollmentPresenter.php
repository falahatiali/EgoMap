<?php

namespace Modules\MissionEngine\Support;

use Modules\MissionEngine\Models\MissionEnrollment;

final class MissionEnrollmentPresenter
{
    public function __construct(
        private readonly MissionEnrollment $enrollment,
    ) {}

    /**
     * @return list<array{key: string, label: string, config: array<string, mixed>}>
     */
    public function enabledCapabilities(string $locale): array
    {
        $capabilities = [];

        foreach ($this->enrollment->template_snapshot['capabilities'] ?? [] as $capability) {
            $label = $capability['label'][$locale]
                ?? $capability['label']['en']
                ?? $capability['key'];

            $capabilities[] = [
                'key' => $capability['key'],
                'label' => is_string($label) ? $label : (string) $capability['key'],
                'config' => $capability['config'] ?? [],
            ];
        }

        return $capabilities;
    }

    public function title(string $locale): string
    {
        if (filled($this->enrollment->title)) {
            return $this->enrollment->title;
        }

        $title = $this->enrollment->template_snapshot['title'][$locale]
            ?? $this->enrollment->template_snapshot['title']['en']
            ?? __('missions.untitled');

        return is_string($title) ? $title : __('missions.untitled');
    }

    /**
     * @return array<string, mixed>
     */
    public function fieldValues(): array
    {
        return $this->enrollment->field_values ?? [];
    }

    public function enrollment(): MissionEnrollment
    {
        return $this->enrollment;
    }
}
