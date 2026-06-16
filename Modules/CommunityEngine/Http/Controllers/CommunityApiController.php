<?php

namespace Modules\CommunityEngine\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\LocaleConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Modules\CommunityEngine\Enums\ReactionType;
use Modules\CommunityEngine\Models\CommunityComment;
use Modules\CommunityEngine\Models\CommunityPost;
use Modules\CommunityEngine\Services\CommunityApiPresenter;
use Modules\CommunityEngine\Services\CommunityCommentService;
use Modules\CommunityEngine\Services\CommunityPostService;

class CommunityApiController extends Controller
{
    public function __construct(
        private readonly CommunityPostService $posts,
        private readonly CommunityCommentService $comments,
        private readonly CommunityApiPresenter $presenter,
    ) {}

    /**
     * GET /api/v1/community/posts
     * Public feed (no auth required).
     */
    public function feed(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sort' => ['sometimes', 'string', Rule::in(['latest', 'liked', 'discussed', 'mine'])],
            'per_page' => ['sometimes', 'integer', 'min:5', 'max:30'],
            'include_preview' => ['sometimes', 'boolean'],
        ]);

        $locale = LocaleConfig::resolve($request->header('Accept-Language'));
        $viewerId = Auth::id();
        $includePreview = $validated['include_preview'] ?? true;

        $feed = $this->posts->feed(
            sort: $validated['sort'] ?? 'latest',
            viewerId: $viewerId,
            perPage: $validated['per_page'] ?? 10,
        );

        $previews = $includePreview
            ? $this->presenter->presentCommentsPreviewsForPosts(collect($feed->items()), $viewerId)
            : [];

        $data = collect($feed->items())
            ->map(function (CommunityPost $post) use ($locale, $viewerId, $previews, $includePreview): array {
                $preview = $includePreview ? ($previews[$post->id] ?? null) : null;

                return $this->presenter->presentPost($post, $locale, $viewerId, $preview);
            })
            ->values()
            ->all();

        return response()->json([
            'locale' => $locale,
            'labels' => $this->presenter->labels($locale),
            'sort_options' => $this->presenter->sortOptions($locale),
            'reaction_groups' => $this->presenter->reactionGroups(),
            'reaction_types' => ReactionType::forUi(),
            'preview_limit' => CommunityCommentService::FEED_PREVIEW_LIMIT,
            'comments_per_page' => CommunityCommentService::POST_PAGE_LIMIT,
            'data' => $data,
            'meta' => [
                'current_page' => $feed->currentPage(),
                'last_page' => $feed->lastPage(),
                'total' => $feed->total(),
                'per_page' => $feed->perPage(),
            ],
        ]);
    }

    /**
     * GET /api/v1/community/posts/{post}
     * Public post detail.
     */
    public function show(Request $request, int $post): JsonResponse
    {
        $locale = LocaleConfig::resolve($request->header('Accept-Language'));
        $viewerId = Auth::id();

        $postModel = $this->posts->findForDisplay($post, $viewerId);
        $this->posts->incrementView($postModel);

        return response()->json([
            'locale' => $locale,
            'labels' => $this->presenter->labels($locale),
            'reaction_groups' => $this->presenter->reactionGroups(),
            'reaction_types' => ReactionType::forUi(),
            'comments_per_page' => CommunityCommentService::POST_PAGE_LIMIT,
            'post' => $this->presenter->presentPost($postModel, $locale, $viewerId),
        ]);
    }

    /**
     * POST /api/v1/community/posts
     * Requires auth.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'min:5', 'max:1000'],
            'is_anonymous' => ['sometimes', 'boolean'],
        ]);

        $locale = LocaleConfig::resolve($request->header('Accept-Language'));

        $result = $this->posts->create(
            user: Auth::user(),
            content: $validated['content'],
            isAnonymous: $validated['is_anonymous'] ?? false,
        );

        if ($result['rejected']) {
            return response()->json([
                'message' => $result['message'],
                'rejected' => true,
            ], 422);
        }

        $post = $result['post']->load('author:id,name');

        return response()->json([
            'post' => $this->presenter->presentPost($post, $locale, Auth::id()),
            'message' => $result['message'],
        ], 201);
    }

    /**
     * DELETE /api/v1/community/posts/{post}
     * Requires auth (own post or admin).
     */
    public function destroy(int $post): JsonResponse
    {
        $postModel = CommunityPost::findOrFail($post);
        $deleted = $this->posts->delete($postModel, Auth::user());

        if (! $deleted) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return response()->json(['message' => __('community.post_deleted')]);
    }

    /**
     * POST /api/v1/community/posts/{post}/react
     * Requires auth.
     */
    public function react(Request $request, int $post): JsonResponse
    {
        $validated = $request->validate([
            'reaction_type' => ['required', 'string', Rule::enum(ReactionType::class)],
        ]);

        $postModel = CommunityPost::approved()->findOrFail($post);
        $type = ReactionType::from($validated['reaction_type']);

        $newReaction = $this->posts->toggleReaction($postModel, Auth::user(), $type);
        $freshPost = $this->posts->findForDisplay($post, Auth::id());

        return response()->json([
            'reaction' => $this->presenter->presentViewerReaction($newReaction?->value),
            'likes_count' => $freshPost->likes_count,
            'post' => $this->presenter->presentPost(
                $freshPost,
                LocaleConfig::resolve($request->header('Accept-Language')),
                Auth::id(),
            ),
        ]);
    }

    /**
     * GET /api/v1/community/posts/{post}/comments
     * Public.
     */
    public function comments(Request $request, int $post): JsonResponse
    {
        $validated = $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'offset' => ['sometimes', 'integer', 'min:0'],
        ]);

        $locale = LocaleConfig::resolve($request->header('Accept-Language'));
        $viewerId = Auth::id();
        $limit = $validated['limit'] ?? CommunityCommentService::POST_PAGE_LIMIT;
        $offset = $validated['offset'] ?? 0;

        $postModel = CommunityPost::approved()->findOrFail($post);
        $result = $this->comments->forPost($postModel, $viewerId, $limit, $offset);
        $presented = $this->presenter->presentCommentsResult($result, $viewerId);

        return response()->json([
            'locale' => $locale,
            'labels' => $this->presenter->labels($locale),
            'reaction_types' => ReactionType::forUi(),
            'data' => $presented['data'],
            'meta' => [
                'has_more' => $presented['has_more'],
                'total' => $presented['total'],
                'limit' => $limit,
                'offset' => $offset,
                'next_offset' => $presented['has_more'] ? $offset + $limit : null,
            ],
        ]);
    }

    /**
     * POST /api/v1/community/posts/{post}/comments
     * Requires auth.
     */
    public function storeComment(Request $request, int $post): JsonResponse
    {
        $postModel = CommunityPost::approved()->findOrFail($post);

        $validated = $request->validate([
            'content' => ['required', 'string', 'min:2', 'max:500'],
            'is_anonymous' => ['sometimes', 'boolean'],
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:community_comments,id'],
        ]);

        $result = $this->comments->create(
            user: Auth::user(),
            post: $postModel,
            content: $validated['content'],
            isAnonymous: $validated['is_anonymous'] ?? false,
            parentId: $validated['parent_id'] ?? null,
        );

        if ($result['rejected']) {
            return response()->json([
                'message' => $result['message'],
                'rejected' => true,
            ], 422);
        }

        $comment = $result['comment']->load(['author:id,name', 'replies']);

        return response()->json([
            'comment' => $this->presenter->presentComment($comment, Auth::id()),
            'message' => $result['message'],
        ], 201);
    }

    /**
     * DELETE /api/v1/community/comments/{comment}
     * Requires auth.
     */
    public function destroyComment(int $commentId): JsonResponse
    {
        $comment = CommunityComment::findOrFail($commentId);
        $deleted = $this->comments->delete($comment, Auth::user());

        if (! $deleted) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return response()->json(['message' => 'Comment removed.']);
    }

    /**
     * POST /api/v1/community/comments/{comment}/react
     * Requires auth.
     */
    public function reactToComment(Request $request, int $commentId): JsonResponse
    {
        $validated = $request->validate([
            'reaction_type' => ['required', 'string', Rule::enum(ReactionType::class)],
        ]);

        $comment = CommunityComment::findOrFail($commentId);
        $type = ReactionType::from($validated['reaction_type']);

        $newReaction = $this->comments->toggleReaction($comment, Auth::user(), $type);

        return response()->json([
            'reaction' => $this->presenter->presentViewerReaction($newReaction?->value),
            'likes_count' => $comment->fresh()?->likes_count ?? $comment->likes_count,
        ]);
    }
}
