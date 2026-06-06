<?php

namespace Modules\AetherEngine\Data;

readonly class ProgramCoachNarrative
{
    public function __construct(
        public ?string $title = null,
        public ?string $weekFocus = null,
        public ?string $mindsetFocus = null,
        public ?string $habitStack = null,
        public ?string $recoveryStrategy = null,
        public ?string $supplementAdvice = null,
        public ?string $disclaimer = null,
    ) {}

    /**
     * @param  array<string, mixed>  $narrative
     */
    public static function fromEnrichmentArray(array $narrative): self
    {
        $firstWeek = is_array($narrative['weeks'][0] ?? null) ? $narrative['weeks'][0] : [];

        return new self(
            title: self::stringOrNull($narrative['title'] ?? null),
            weekFocus: self::stringOrNull($firstWeek['focus'] ?? $narrative['week_focus'] ?? null),
            mindsetFocus: self::stringOrNull($firstWeek['mindset_focus'] ?? $narrative['mindset_focus'] ?? null),
            habitStack: self::stringOrNull($firstWeek['habit_stack'] ?? $narrative['habit_stack'] ?? null),
            recoveryStrategy: self::stringOrNull($narrative['recovery_strategy'] ?? null),
            supplementAdvice: self::stringOrNull($narrative['supplement_advice'] ?? null),
            disclaimer: self::stringOrNull($narrative['disclaimer'] ?? null),
        );
    }

    /**
     * @return array<string, ?string>
     */
    public function toProgramColumns(): array
    {
        return [
            'coach_title' => $this->title,
            'coach_week_focus' => $this->weekFocus,
            'coach_mindset_focus' => $this->mindsetFocus,
            'coach_habit_stack' => $this->habitStack,
            'coach_recovery_strategy' => $this->recoveryStrategy,
            'coach_supplement_advice' => $this->supplementAdvice,
            'coach_disclaimer' => $this->disclaimer,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function toDisplayMap(): array
    {
        return array_filter([
            'title' => $this->title,
            'week_focus' => $this->weekFocus,
            'mindset_focus' => $this->mindsetFocus,
            'habit_stack' => $this->habitStack,
            'recovery_strategy' => $this->recoveryStrategy,
            'supplement_advice' => $this->supplementAdvice,
            'disclaimer' => $this->disclaimer,
        ], static fn (?string $value): bool => is_string($value) && $value !== '');
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
