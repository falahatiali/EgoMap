<?php

namespace App\Support\Ai;

use MischaSigtermans\Toon\Facades\Toon;

class ToonPromptBuilder
{
    /**
     * @param  array<string, mixed>  $context
     */
    public static function encode(array $context): string
    {
        return Toon::encode($context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function build(string $instructions, array $context, string $task): string
    {
        $toon = self::encode($context);

        return <<<PROMPT
{$instructions}

USER DATA (TOON — compact structured format):
{$toon}

{$task}
PROMPT;
    }
}
