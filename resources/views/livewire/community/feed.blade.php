<div class="community-page" x-data="{ showCreateModal: @entangle('showCreateModal') }">

    <div class="community-shell">
        {{-- ── Header ── --}}
        <div class="community-header">
            <div class="community-header__inner">
                <div>
                    <h1 class="community-header__title">{{ __('community.title') }}</h1>
                    <p class="community-header__subtitle">{{ __('community.subtitle') }}</p>
                </div>
                @auth
                    <button wire:click="openCreateModal" class="eg-btn eg-btn--primary community-fab">
                        <span class="community-fab__icon">+</span>
                        <span class="community-fab__label">{{ __('community.new_post') }}</span>
                    </button>
                @endauth
            </div>
        </div>

        {{-- ── Flash ── --}}
        @if (session('community_status'))
            <div class="community-toast" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                x-transition:leave="opacity-0 translate-y-1 transition-all duration-300">
                {{ session('community_status') }}
            </div>
        @endif

        {{-- ── Sort Tabs ── --}}
        <div class="community-sort-bar">
            @foreach ($sortOptions as $key => $label)
                <button wire:click="$set('sort', '{{ $key }}')"
                    class="community-sort-btn {{ $sort === $key ? 'community-sort-btn--active' : '' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- ── Feed ── --}}
        <div class="community-feed" wire:loading.class="community-feed--loading">
            @forelse ($posts as $post)
                <article class="community-card" wire:key="post-{{ $post->id }}">

                    {{-- Card Header --}}
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
                        @if (Auth::check() && $post->isOwnedBy(Auth::id()))
                            <button wire:click="deletePost({{ $post->id }})"
                                x-on:click="confirm(@js(__('community.confirm_delete_post'))) || $event.stopImmediatePropagation()"
                                class="community-card__delete-btn"
                                title="{{ __('community.delete') }}">
                                <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                            </button>
                        @endif
                    </div>

                    <a href="{{ route('community.show', ['locale' => $locale, 'post' => $post->id]) }}"
                        wire:navigate class="community-card__content-link">
                        <div class="community-card__content">{{ $post->content }}</div>
                    </a>

                    {{-- Reactions --}}
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

                    {{-- Comment preview (last 3) --}}
                    <div class="community-card__comments">
                        @if ($post->comments_count > 0)
                            <div class="community-comments__heading">
                                <i class="fa-regular fa-comment-dots" aria-hidden="true"></i>
                                <span>{{ trans_choice('community.comment_count', $post->comments_count, ['count' => $post->comments_count]) }}</span>
                            </div>
                        @endif
                        @livewire('community.comment-section', [
                            'postId' => $post->id,
                            'preview' => true,
                        ], key('comments-'.$post->id))
                    </div>

                </article>
            @empty
                <div class="community-empty">
                    <div class="community-empty__icon">💬</div>
                    <p class="community-empty__text">{{ __('community.empty_feed') }}</p>
                    @auth
                        <button wire:click="openCreateModal" class="eg-btn eg-btn--primary">
                            {{ __('community.be_first') }}
                        </button>
                    @endauth
                </div>
            @endforelse
        </div>

        {{-- ── Pagination ── --}}
        <div class="community-pagination">
            {{ $posts->links() }}
        </div>
    </div>

    {{-- ── Create Post Modal ── --}}
    <div x-show="showCreateModal" x-transition class="community-modal-overlay"
        x-on:click.self="$wire.closeCreateModal()">
        <div class="community-modal">
            <div class="community-modal__header">
                <h2>{{ __('community.share_with_community') }}</h2>
                <button wire:click="closeCreateModal" class="community-modal__close">✕</button>
            </div>
            @livewire('community.create-post', key('create-post'))
        </div>
    </div>

</div>
