<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\Profile\UserQuizHistoryService;
use App\Support\LocaleConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(
        Request $request,
        UserQuizHistoryService $historyService,
    ): JsonResponse {
        $user = $request->user('sanctum');
        abort_unless($user !== null, 401);

        $locale = LocaleConfig::resolve(app()->getLocale());
        $filter = (string) $request->query('tests_filter', 'all');

        if (! in_array($filter, ['all', 'in_progress', 'completed'], true)) {
            $filter = 'all';
        }

        $records = $historyService->apiRecordsForUser($user, $locale);
        $inProgress = $records->filter(fn (array $record): bool => (bool) ($record['is_in_progress'] ?? false))->values();
        $completed = $records->filter(fn (array $record): bool => ! ($record['is_in_progress'] ?? false))->values();

        $filtered = match ($filter) {
            'in_progress' => $inProgress,
            'completed' => $completed,
            default => $records,
        };

        return response()->json([
            'user' => new UserResource($user),
            'stats' => [
                'total' => $records->count(),
                'in_progress' => $inProgress->count(),
                'completed' => $completed->count(),
            ],
            'tests_filter' => $filter,
            'tests' => $filtered->values()->all(),
            'labels' => [
                'page_title' => __('profile.page_title', locale: $locale),
                'member' => __('profile.member', locale: $locale),
                'verified' => __('profile.verified', locale: $locale),
                'my_tests_title' => __('profile.my_tests_title', locale: $locale),
                'my_tests_subtitle' => __('profile.my_tests_subtitle', locale: $locale),
                'take_new_test' => __('profile.take_new_test', locale: $locale),
                'filter_all' => __('profile.filter_all', locale: $locale),
                'filter_in_progress' => __('profile.filter_in_progress', locale: $locale),
                'filter_completed' => __('profile.filter_completed', locale: $locale),
                'no_tests_title' => __('profile.no_tests_title', locale: $locale),
                'no_tests_body' => __('profile.no_tests_body', locale: $locale),
            ],
        ]);
    }
}
