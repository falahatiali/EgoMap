<?php

namespace Modules\CommunityEngine\Services;

use Modules\CommunityEngine\Ai\Agents\ContentModerationAgent;

/**
 * Checks content safety before publishing posts or comments.
 *
 * @phpstan-type ModerationResult array{is_safe: bool, flags: list<string>, reason: string, suggested_message: string}
 */
class CommunityModerationService
{
    /**
     * Check whether content is safe to publish.
     *
     * @return ModerationResult
     */
    public function check(string $content): array
    {
        try {
            /** @var ModerationResult $result */
            $result = (new ContentModerationAgent)->prompt(
                "Check this community post/comment for safety:\n\n".e($content)
            );

            return $result;
        } catch (\Throwable) {
            // Fail open: if AI is unavailable, allow the post and log separately.
            return [
                'is_safe' => true,
                'flags' => [],
                'reason' => 'AI moderation unavailable; content passed by default.',
                'suggested_message' => '',
            ];
        }
    }

    public function isSafe(string $content): bool
    {
        return $this->check($content)['is_safe'];
    }
}
