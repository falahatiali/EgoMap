<?php

namespace Modules\AetherEngine\Services;

class InjuryTagResolver
{
    /**
     * @var array<string, array<int, string>>
     */
    private const KEYWORD_MAP = [
        'knee' => ['knee', 'patella', 'acl', 'meniscus'],
        'lower_back' => ['lower back', 'lumbar', 'disc', 'sciatica'],
        'shoulder' => ['shoulder', 'rotator', 'impingement'],
        'wrist' => ['wrist', 'carpal'],
        'ankle' => ['ankle', 'achilles'],
        'hip' => ['hip', 'groin'],
        'neck' => ['neck', 'cervical'],
    ];

    /**
     * @return array<int, string>
     */
    public function resolve(?string $freeText, ?array $explicitTags = null): array
    {
        $tags = collect($explicitTags ?? []);

        if ($freeText !== null && trim($freeText) !== '') {
            $normalized = strtolower($freeText);

            foreach (self::KEYWORD_MAP as $tag => $keywords) {
                foreach ($keywords as $keyword) {
                    if (str_contains($normalized, $keyword)) {
                        $tags->push($tag);

                        break;
                    }
                }
            }
        }

        return $tags->unique()->values()->all();
    }
}
