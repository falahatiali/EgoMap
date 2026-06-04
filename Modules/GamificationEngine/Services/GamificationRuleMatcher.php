<?php

namespace Modules\GamificationEngine\Services;

use Modules\GamificationEngine\Models\GamificationRule;

/**
 * Compares rule.conditions keys to event metadata (exact match; arrays = in_list).
 */
class GamificationRuleMatcher
{
    /**
     * True when every condition key exists in metadata with equal value (or in array list).
     *
     * @param  array<string, mixed>  $metadata  Passed from dispatch context, e.g. ['trigger' => 'sent_message']
     */
    public function matches(GamificationRule $rule, array $metadata): bool
    {
        $conditions = $rule->conditions;

        if (! is_array($conditions) || $conditions === []) {
            return true;
        }

        foreach ($conditions as $key => $expected) {
            if (! array_key_exists($key, $metadata)) {
                return false;
            }

            $actual = $metadata[$key];

            if (is_array($expected)) {
                if (! in_array($actual, $expected, true)) {
                    return false;
                }

                continue;
            }

            if (is_bool($expected) || is_bool($actual)) {
                if ((bool) $actual !== (bool) $expected) {
                    return false;
                }

                continue;
            }

            if (is_int($expected) || is_float($expected)) {
                if ((int) $actual !== (int) $expected) {
                    return false;
                }

                continue;
            }

            if ($actual != $expected) {
                return false;
            }
        }

        return true;
    }
}
