<?php

namespace Modules\CommunityEngine\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Modules\CommunityEngine\Enums\ReactionType;
use Modules\CommunityEngine\Models\CommunityComment;
use Modules\CommunityEngine\Models\CommunityPost;
use Modules\CommunityEngine\Services\CommunityCommentService;
use Modules\CommunityEngine\Services\CommunityPostService;

class CommunityApiController extends Controller
{
    public function __construct(
        private readonly CommunityPostService $posts,
        private readonly CommunityCommentService $comments,
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
        ]);

        $feed = $this->posts->feed(
            sort: $validated['sort'] ?? 'latest',
            viewerId: Auth::id(),
            perPage: $validated['per_page'] ?? 10,
        );

        return response()->json([
            'data' => $feed->items(),
            'meta' => [
                'current_page' => $feed->currentPage(),
                'last_page' => $feed->lastPage(),
                'total' => $feed->total(),
                'per_page' => $feed->perPage(),
            ],
            'reaction_types' => ReactionType::forUi(),
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

        return response()->json([
            'post' => $result['post'],
            'message' => $result['message'],
        ], 201);
    }

    /**
     * DELETE /api/v1/community/posts/{post}
     * Requires auth (own post or admin).
     */
    public function destroy(int $postId): JsonResponse
    {
        $post = CommunityPost::findOrFail($postId);
        $deleted = $this->posts->delete($post, Auth::user());

        if (! $deleted) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return response()->json(['message' => 'Post removed.']);
    }

    /**
     * POST /api/v1/community/posts/{post}/react
     * Requires auth.
     */
    public function react(Request $request, int $postId): JsonResponse
    {
        $validated = $request->validate([
            'reaction_type' => ['required', 'string', Rule::enum(ReactionType::class)],
        ]);

        $post = CommunityPost::approved()->findOrFail($postId);
        $type = ReactionType::from($validated['reaction_type']);

        $newReaction = $this->posts->toggleReaction($post, Auth::user(), $type);

        return response()->json([
            'reaction' => $newReaction?->value,
            'likes_count' => $post->fresh()?->likes_count ?? $post->likes_count,
        ]);
    }

    /**
     * GET /api/v1/community/posts/{post}/comments
     * Public.
     */
    public function comments(int $postId): JsonResponse
    {
        $post = CommunityPost::approved()->findOrFail($postId);
        $comments = $this->comments->forPost($post);

        return response()->json(['data' => $comments]);
    }

    /**
     * POST /api/v1/community/posts/{post}/comments
     * Requires auth.
     */
    public function storeComment(Request $request, int $postId): JsonResponse
    {
        $post = CommunityPost::approved()->findOrFail($postId);

        $validated = $request->validate([
            'content' => ['required', 'string', 'min:2', 'max:500'],
            'is_anonymous' => ['sometimes', 'boolean'],
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:community_comments,id'],
        ]);

        $result = $this->comments->create(
            user: Auth::user(),
            post: $post,
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

        return response()->json([
            'comment' => $result['comment'],
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
            'reaction' => $newReaction?->value,
            'likes_count' => $comment->fresh()?->likes_count ?? $comment->likes_count,
        ]);
    }
}
