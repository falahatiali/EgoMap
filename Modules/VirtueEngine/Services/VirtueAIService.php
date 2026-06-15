<?php

namespace Modules\VirtueEngine\Services;

use Illuminate\Support\Facades\Log;
use Modules\VirtueEngine\Ai\Agents\HabitCoachAgent;
use Modules\VirtueEngine\Ai\Agents\SlipEncouragerAgent;

class VirtueAIService
{
    /**
     * @return array{root_cause: string, steps: list<array{order: int, action: string, daily_practice: string}>, affirmation: string, category: string}|null
     */
    public function analyzeHabit(string $habitDescription): ?array
    {
        if (! config('virtue.ai_coaching_enabled')) {
            return null;
        }

        try {
            $prompt = <<<PROMPT
User's self-described bad habit:
"{$habitDescription}"

Analyse this habit and return your structured coaching plan as specified.
PROMPT;

            $response = (new HabitCoachAgent)->prompt($prompt);

            return $response->structured ?? null;
        } catch (\Throwable $e) {
            Log::warning('VirtueAI habit analysis failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @return array{acknowledgement: string, micro_task: string, motivation_close: string, points_deducted_message: string}|null
     */
    public function generateSlipResponse(string $habitDescription, string $whatHappened): ?array
    {
        if (! config('virtue.ai_coaching_enabled')) {
            return null;
        }

        try {
            $prompt = <<<PROMPT
The user is working on fixing this bad habit:
"{$habitDescription}"

Today they slipped and reported:
"{$whatHappened}"

Generate your personalised recovery message now.
PROMPT;

            $response = (new SlipEncouragerAgent)->prompt($prompt);

            return $response->structured ?? null;
        } catch (\Throwable $e) {
            Log::warning('VirtueAI slip response failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Generates a single short encouragement line for a logged success.
     */
    public function encourageSuccess(string $habitDescription, string $situation): ?string
    {
        if (! config('virtue.ai_coaching_enabled')) {
            return null;
        }

        try {
            $prompt = <<<PROMPT
The user is working on fixing: "{$habitDescription}"

Today they succeeded and reported: "{$situation}"

Write one warm, specific, short encouragement sentence (max 20 words). No preamble, just the sentence.
PROMPT;

            $response = (new HabitCoachAgent)->prompt($prompt);

            return is_string($response->text) ? $response->text : null;
        } catch (\Throwable $e) {
            Log::warning('VirtueAI encouragement failed', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
