<div @class([
    'community-comment__actions',
    'community-comment__actions--has-selection' => (bool) ($comment->viewer_reaction ?? null),
])>
    @if ($comment->viewer_reaction ?? null)
        @php $viewerReaction = \Modules\CommunityEngine\Enums\ReactionType::tryFrom($comment->viewer_reaction); @endphp
        @if ($viewerReaction)
            <span @class([
                'community-comment__your-reaction',
                'community-comment__your-reaction--empathetic' => $viewerReaction->tone() === 'empathetic',
            ])>
                {{ $viewerReaction->emoji() }} {{ $viewerReaction->label() }}
            </span>
        @endif
    @endif

    @foreach ($reactionTypes as $rt)
        @php
            $isActive = ($comment->viewer_reaction ?? null) === $rt['type'];
            $isEmpathetic = ($rt['tone'] ?? '') === 'empathetic';
        @endphp
        <button
            wire:click="toggleCommentReaction({{ $comment->id }}, '{{ $rt['type'] }}')"
            wire:loading.attr="disabled"
            wire:target="toggleCommentReaction"
            class="community-reaction-btn community-reaction-btn--sm {{ $isActive ? 'community-reaction-btn--active' : '' }} {{ $isEmpathetic ? 'community-reaction-btn--empathetic' : '' }}"
            title="{{ $rt['label'] }}"
            aria-pressed="{{ $isActive ? 'true' : 'false' }}">
            <span class="community-reaction-btn__emoji">{{ $rt['emoji'] }}</span>
        </button>
    @endforeach

    @if ($comment->likes_count > 0)
        <span class="community-comment__likes">{{ $comment->likes_count }}</span>
    @endif

    @auth
        <button wire:click="startReply({{ $comment->id }})"
            class="community-comment__reply-btn">
            {{ __('community.reply') }}
        </button>
    @endauth
</div>
