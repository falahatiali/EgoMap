<?php

namespace App\Support;

final readonly class NavProfileData
{
    public function __construct(
        public string $name,
        public string $initial,
        public ?string $planBadge = null,
        public ?string $planPeriod = null,
    ) {}
}
