<?php

namespace App\Ai\Agents\Recovery;

use App\Support\LocaleConfig;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

abstract class LocalizedRecoveryAgent implements Agent
{
    use Promptable;

    public function __construct(public string $locale = 'en') {}

    abstract protected function instructionsTranslationKey(): string;

    public function instructions(): Stringable|string
    {
        return LocaleConfig::translate(
            $this->instructionsTranslationKey(),
            $this->locale,
            ['language' => LocaleConfig::aiLanguageName($this->locale)],
        );
    }
}
