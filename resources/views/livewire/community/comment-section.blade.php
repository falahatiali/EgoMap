<div class="community-comments">
    {{-- New comment form (post page only) --}}
    @unless ($preview)
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
    @endunless

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
                    @if (! $preview && Auth::check() && $comment->isOwnedBy(Auth::id()))
                        <button wire:click="deleteComment({{ $comment->id }})"
                            x-on:click="confirm('Remove this comment?') || $event.stopImmediatePropagation()"
                            class="community-comment__delete">✕</button>
                    @endif
                </div>

                <div class="community-comment__content">{{ $comment->content }}</div>

                @unless ($preview)
                    @include('livewire.community.partials.comment-reactions', ['comment' => $comment])
                @endunless

                @if (! $preview && $replyingTo === $comment->id)
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
                                    <span class="community-comment__time">{{ $reply->created_at->diffForHumans() }}</span>
                                    @if (! $preview && Auth::check() && $reply->isOwnedBy(Auth::id()))
                                        <button wire:click="deleteComment({{ $reply->id }})"
                                            class="community-comment__delete">✕</button>
                                    @endif
                                </div>
                                <div class="community-comment__content">{{ $reply->content }}</div>

                                @unless ($preview)
                                    @include('livewire.community.partials.comment-reactions', ['comment' => $reply])
                                @endunless

                                @if ($reply->replies->isNotEmpty())
                                    <div class="community-comment__replies">
                                        @foreach ($reply->replies->take(3) as $deepReply)
                                            <div class="community-comment community-comment--deep"
                                                wire:key="deep-{{ $deepReply->id }}">
                                                <div class="community-comment__header">
                                                    <span class="community-comment__avatar community-comment__avatar--sm">
                                                        {{ strtoupper(substr($deepReply->displayName(), 0, 1)) }}
                                                    </span>
                                                    <span class="community-comment__author">{{ $deepReply->displayName() }}</span>
                                                    <span class="community-comment__time">{{ $deepReply->created_at->diffForHumans() }}</span>
                                                </div>
                                                <div class="community-comment__content">{{ $deepReply->content }}</div>
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

    @if ($preview && ($commentsCount > \Modules\CommunityEngine\Services\CommunityCommentService::FEED_PREVIEW_LIMIT || $hasMore))
        <a href="{{ route('community.show', ['locale' => app()->getLocale(), 'post' => $postId]) }}"
            wire:navigate class="community-view-all-link">
            {{ __('community.view_all_comments', ['count' => $commentsCount]) }}
            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </a>
    @elseif (! $preview && $hasMore)
        <div class="community-comments__load-more">
            <button wire:click="loadMore" class="eg-btn eg-btn--ghost community-load-more-btn"
                wire:loading.attr="disabled" wire:target="loadMore">
                <span wire:loading.remove wire:target="loadMore">{{ __('community.load_more_comments') }}</span>
                <span wire:loading wire:target="loadMore">{{ __('community.loading_comments') }}</span>
            </button>
        </div>
    @endif
</div>
