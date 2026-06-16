<div class="community-page">

    <div class="community-shell community-shell--post">
        <a href="{{ route('community.feed', ['locale' => $locale]) }}" wire:navigate class="community-back-link">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            {{ __('community.back_to_feed') }}
        </a>

        <article class="community-card community-card--detail">
            @include('livewire.community.partials.post-body', [
                'post' => $post,
                'reactionGroups' => $reactionGroups,
            ])

            <div class="community-card__comments">
                <div class="community-comments__heading">
                    <i class="fa-regular fa-comment-dots" aria-hidden="true"></i>
                    <span>{{ trans_choice('community.comment_count', $post->comments_count, ['count' => $post->comments_count]) }}</span>
                </div>

                @livewire('community.comment-section', [
                    'postId' => $post->id,
                    'preview' => false,
                    'perPage' => $commentsPerPage,
                ], key('post-comments-'.$post->id))
            </div>
        </article>
    </div>

</div>
