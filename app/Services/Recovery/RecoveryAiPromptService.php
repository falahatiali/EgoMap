<?php

namespace App\Services\Recovery;

use App\Support\Ai\ToonPromptBuilder;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

class RecoveryAiPromptService
{
    public function defaultProviderName(): string
    {
        return (string) config('ai.default');
    }

    public function defaultProvider(): Lab
    {
        return Lab::from($this->defaultProviderName());
    }

    public function isEnabled(): bool
    {
        $provider = $this->defaultProviderName();

        return filled(config("ai.providers.{$provider}.key"));
    }

    public function model(): ?string
    {
        $model = config('recovery_ai.model');

        return filled($model) ? (string) $model : null;
    }

    public function promptVersion(): string
    {
        return (string) config('recovery_ai.prompt_version', 'recovery-v1');
    }

    public function modelLabel(): string
    {
        $provider = $this->defaultProviderName();
        $model = $this->model();

        return $model !== null ? "{$provider}:{$model}" : $provider;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function buildPrompt(string $instructions, array $context, string $task): string
    {
        return ToonPromptBuilder::build($instructions, $context, $task);
    }

    /**
     * @return mixed
     */
    public function prompt(Agent $agent, string $prompt)
    {
        $this->assertAgentIsPromptable($agent);

        $model = $this->model();

        if ($model !== null) {
            return $agent->prompt($prompt, provider: $this->defaultProvider(), model: $model);
        }

        return $agent->prompt($prompt, provider: $this->defaultProvider());
    }

    protected function assertAgentIsPromptable(Agent $agent): void
    {
        if (! in_array(Promptable::class, class_uses_recursive($agent), true)) {
            throw new \InvalidArgumentException(sprintf(
                'Agent [%s] must use the Promptable trait.',
                $agent::class,
            ));
        }
    }
}
