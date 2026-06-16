<div class="community-page" x-data="{ showCreateModal: @entangle('showCreateModal') }">

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
            <div class="community-card" wire:key="post-{{ $post->id }}"
                x-data="{ showComments: false }">

                {{-- Card Header --}}
                <div class="community-card__header">
                    <div class="community-card__avatar">
                        {{ strtoupper(substr($post->displayName(), 0, 1)) }}
                    </div>
                    <div class="community-card__meta">
                        <span class="community-card__author">{{ $post->displayName() }}</span>
                        <span class="community-card__time">{{ $post->created_at->diffForHumans() }}</span>
                    </div>
                    @if (Auth::check() && $post->isOwnedBy(Auth::id()))
                        <button wire:click="$dispatch('post-deleted')"
                            x-on:click="if(confirm('Delete this post?')) $wire.call('$dispatch', 'post-deleted')"
                            class="community-card__delete-btn"
                            title="{{ __('community.delete') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" class="w-4 h-4">
                                <polyline points="3,6 5,6 21,6" /><path
                                    d="M19,6l-1,14a2,2,0,0,1-2,2H8a2,2,0,0,1-2-2L5,6" /><path
                                    d="M10,11v6m4-6v6" />
                            </svg>
                        </button>
                    @endif
                </div>

                {{-- Content --}}
                <div class="community-card__content">{{ $post->content }}</div>

                {{-- Reactions --}}
                <div class="community-card__reactions">
                    @foreach ($reactionTypes as $reaction)
                        <button
                            wire:click="toggleReaction({{ $post->id }}, '{{ $reaction['type'] }}')"
                            class="community-reaction-btn {{ ($post->viewer_reaction ?? null) === $reaction['type'] ? 'community-reaction-btn--active' : '' }}"
                            title="{{ $reaction['label'] }}">
                            <span class="community-reaction-btn__emoji">{{ $reaction['emoji'] }}</span>
                        </button>
                    @endforeach
                    <span class="community-card__likes">
                        {{ $post->likes_count }} {{ __('community.reactions') }}
                    </span>
                </div>

                {{-- Actions --}}
                <div class="community-card__actions">
                    <button x-on:click="showComments = !showComments"
                        class="community-action-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" class="w-4 h-4">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                        </svg>
                        {{ $post->comments_count }} {{ __('community.comments') }}
                    </button>
                    <button
                        x-on:click="navigator.clipboard?.writeText(window.location.origin+'/community/posts/{{ $post->id }}').then(() => $dispatch('community-copied'))"
                        class="community-action-btn" title="{{ __('community.share') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" class="w-4 h-4">
                            <circle cx="18" cy="5" r="3" /><circle cx="6" cy="12" r="3" /><circle cx="18"
                                cy="19" r="3" />
                            <line x1="8.59" y1="13.51" x2="15.42" y2="17.49" /><line x1="15.41" y1="6.51"
                                x2="8.59" y2="10.49" />
                        </svg>
                        {{ __('community.share') }}
                    </button>
                </div>

                {{-- Comment Section (lazy) --}}
                <div x-show="showComments" x-transition>
                    @livewire('community.comment-section', ['postId' => $post->id], key: 'comments-'.$post->id)
                </div>

            </div>
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

    {{-- ── Create Post Modal ── --}}
    <div x-show="showCreateModal" x-transition class="community-modal-overlay"
        x-on:click.self="$wire.closeCreateModal()">
        <div class="community-modal">
            <div class="community-modal__header">
                <h2>{{ __('community.share_with_community') }}</h2>
                <button wire:click="closeCreateModal" class="community-modal__close">✕</button>
            </div>
            @livewire('community.create-post', key: 'create-post')
        </div>
    </div>

</div>
