{{-- Shared post header, content, and reactions (expects Livewire toggleReaction on parent) --}}
<div class="community-card__header">
    <div class="community-card__avatar">
        {{ strtoupper(substr($post->displayName(), 0, 1)) }}
    </div>
    <div class="community-card__meta">
        <span class="community-card__author">{{ $post->displayName() }}</span>
        <span class="community-card__time">{{ $post->created_at->diffForHumans() }}</span>
    </div>
    @if ($post->likes_count > 0)
        <span class="community-card__stat-pill">
            {{ $post->likes_count }} {{ __('community.reactions') }}
        </span>
    @endif
    @if ($post->comments_count > 0)
        <span class="community-card__stat-pill">
            {{ trans_choice('community.comment_count', $post->comments_count, ['count' => $post->comments_count]) }}
        </span>
    @endif
    @if (Auth::check() && $post->isOwnedBy(Auth::id()))
        <button wire:click="deletePost"
            x-on:click="confirm(@js(__('community.confirm_delete_post'))) || $event.stopImmediatePropagation()"
            class="community-card__delete-btn"
            title="{{ __('community.delete') }}">
            <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
        </button>
    @endif
</div>

<div class="community-card__content">{{ $post->content }}</div>

<div @class([
    'community-card__reactions',
    'community-card__reactions--has-selection' => (bool) ($post->viewer_reaction ?? null),
])>
    @if ($post->viewer_reaction ?? null)
        @php
            $viewerReaction = \Modules\CommunityEngine\Enums\ReactionType::tryFrom($post->viewer_reaction);
        @endphp
        @if ($viewerReaction)
            <div @class([
                'community-your-reaction',
                'community-your-reaction--empathetic' => $viewerReaction->tone() === 'empathetic',
            ])>
                <span class="community-your-reaction__label">{{ __('community.you_reacted') }}</span>
                <span class="community-your-reaction__value">
                    {{ $viewerReaction->emoji() }} {{ $viewerReaction->label() }}
                </span>
            </div>
        @endif
    @endif

    @foreach (['positive' => __('community.reactions_positive'), 'empathetic' => __('community.reactions_empathetic')] as $tone => $toneLabel)
        <div class="community-reaction-group">
            <span class="community-reaction-group__label">{{ $toneLabel }}</span>
            <div class="community-reaction-group__buttons">
                @foreach ($reactionGroups[$tone] as $reaction)
                    @php $isActive = ($post->viewer_reaction ?? null) === $reaction['type']; @endphp
                    <button
                        wire:click="toggleReaction({{ $post->id }}, '{{ $reaction['type'] }}')"
                        wire:loading.attr="disabled"
                        wire:target="toggleReaction"
                        class="community-reaction-btn {{ $isActive ? 'community-reaction-btn--active' : '' }} {{ $tone === 'empathetic' ? 'community-reaction-btn--empathetic' : '' }}"
                        title="{{ $reaction['label'] }}"
                        aria-pressed="{{ $isActive ? 'true' : 'false' }}">
                        <span class="community-reaction-btn__emoji">{{ $reaction['emoji'] }}</span>
                        <span class="community-reaction-btn__label">{{ $reaction['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
