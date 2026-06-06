<?php

namespace App\Services\Billing;

final readonly class PlanSelectionOutcome
{
    public const Redirect = 'redirect';

    public const Changed = 'changed';

    public const Current = 'current';

    public const Error = 'error';

    public function __construct(
        public string $type,
        public ?string $url = null,
        public ?string $message = null,
    ) {}

    public static function redirect(string $url): self
    {
        return new self(self::Redirect, url: $url);
    }

    public static function changed(): self
    {
        return new self(self::Changed);
    }

    public static function current(): self
    {
        return new self(self::Current);
    }

    public static function error(string $message): self
    {
        return new self(self::Error, message: $message);
    }
}
