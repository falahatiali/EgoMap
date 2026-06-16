<div class="community-comments" x-data>
    {{-- Lazy load trigger --}}
    @if (! $loaded)
        <div wire:intersect="load" class="community-comments__loading">
            <span class="community-comments__spinner"></span>
        </div>
    @else

        {{-- New comment form --}}
        @auth
            <div class="community-comments__form">
                <textarea wire:model.live="newComment" class="community-comments__input"
                    placeholder="{{ __('community.write_a_comment') }}" rows="2"
                    maxlength="500"></textarea>
                @error('newComment')
                    <p class="community-create-post__error">{{ $message }}</p>
                @enderror
                <div class="community-comments__form-footer">
                    <label class="community-toggle-label community-toggle-label--sm">
                        <input type="checkbox" wire:model="isAnonymous" />
                        <span>{{ __('community.anonymous') }}</span>
                    </label>
                    <button wire:click="submitComment" class="eg-btn eg-btn--primary eg-btn--sm"
                        wire:loading.attr="disabled">
                        {{ __('community.post_comment') }}
                    </button>
                </div>
            </div>
        @endauth

        {{-- Comment list --}}
        <div class="community-comments__list">
            @forelse ($comments as $comment)
                <div class="community-comment" wire:key="comment-{{ $comment->id }}">
                    <div class="community-comment__header">
                        <span class="community-comment__avatar">
                            {{ strtoupper(substr($comment->displayName(), 0, 1)) }}
                        </span>
                        <span class="community-comment__author">{{ $comment->displayName() }}</span>
                        <span class="community-comment__time">
                            {{ $comment->created_at->diffForHumans() }}
                            @if ($comment->isRecent())
                                <span class="community-comment__new-badge">NEW</span>
                            @endif
                        </span>
                        @if (Auth::check() && $comment->isOwnedBy(Auth::id()))
                            <button wire:click="deleteComment({{ $comment->id }})"
                                x-on:click="confirm('Remove this comment?') || $event.stopImmediatePropagation()"
                                class="community-comment__delete">✕</button>
                        @endif
                    </div>

                    <div class="community-comment__content">{{ $comment->content }}</div>

                    <div class="community-comment__actions">
                        @foreach ($reactionTypes as $rt)
                            <button
                                wire:click="toggleCommentReaction({{ $comment->id }}, '{{ $rt['type'] }}')"
                                class="community-reaction-btn community-reaction-btn--sm"
                                title="{{ $rt['label'] }}">
                                {{ $rt['emoji'] }}
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

                    {{-- Reply form --}}
                    @if ($replyingTo === $comment->id)
                        <div class="community-comment__reply-form">
                            <textarea wire:model.live="replyContent" class="community-comments__input"
                                placeholder="{{ __('community.write_a_reply') }}" rows="2"
                                maxlength="500"></textarea>
                            @error('replyContent')
                                <p class="community-create-post__error">{{ $message }}</p>
                            @enderror
                            <div class="community-comments__form-footer">
                                <button wire:click="cancelReply" class="eg-btn eg-btn--ghost eg-btn--sm">
                                    {{ __('community.cancel') }}
                                </button>
                                <button wire:click="submitReply" class="eg-btn eg-btn--primary eg-btn--sm"
                                    wire:loading.attr="disabled">
                                    {{ __('community.reply') }}
                                </button>
                            </div>
                        </div>
                    @endif

                    {{-- Nested replies (level 2) --}}
                    @if ($comment->replies->isNotEmpty())
                        <div class="community-comment__replies">
                            @foreach ($comment->replies as $reply)
                                <div class="community-comment community-comment--nested"
                                    wire:key="reply-{{ $reply->id }}">
                                    <div class="community-comment__header">
                                        <span class="community-comment__avatar community-comment__avatar--sm">
                                            {{ strtoupper(substr($reply->displayName(), 0, 1)) }}
                                        </span>
                                        <span class="community-comment__author">{{ $reply->displayName() }}</span>
                                        <span
                                            class="community-comment__time">{{ $reply->created_at->diffForHumans() }}</span>
                                        @if (Auth::check() && $reply->isOwnedBy(Auth::id()))
                                            <button wire:click="deleteComment({{ $reply->id }})"
                                                class="community-comment__delete">✕</button>
                                        @endif
                                    </div>
                                    <div class="community-comment__content">{{ $reply->content }}</div>

                                    {{-- Level 3 replies --}}
                                    @if ($reply->replies->isNotEmpty())
                                        <div class="community-comment__replies">
                                            @foreach ($reply->replies->take(3) as $deepReply)
                                                <div class="community-comment community-comment--deep"
                                                    wire:key="deep-{{ $deepReply->id }}">
                                                    <div class="community-comment__header">
                                                        <span
                                                            class="community-comment__avatar community-comment__avatar--sm">
                                                            {{ strtoupper(substr($deepReply->displayName(), 0, 1)) }}
                                                        </span>
                                                        <span
                                                            class="community-comment__author">{{ $deepReply->displayName() }}</span>
                                                        <span
                                                            class="community-comment__time">{{ $deepReply->created_at->diffForHumans() }}</span>
                                                    </div>
                                                    <div
                                                        class="community-comment__content">{{ $deepReply->content }}</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <p class="community-comments__empty">{{ __('community.no_comments_yet') }}</p>
            @endforelse
        </div>
    @endif
</div>
