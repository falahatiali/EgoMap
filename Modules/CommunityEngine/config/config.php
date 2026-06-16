<?php

return [
    'name' => 'CommunityEngine',

    /**
     * When true, every post/comment is passed through ContentModerationAgent before publishing.
     * Disable in tests or when AI credentials are unavailable.
     */
    'auto_moderate' => env('COMMUNITY_AUTO_MODERATE', true),

    /**
     * When true, posts go into "pending" status and require admin approval before showing in the feed.
     * When false (default), posts publish immediately after moderation.
     */
    'require_approval' => env('COMMUNITY_REQUIRE_APPROVAL', false),
];
