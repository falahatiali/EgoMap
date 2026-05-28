<?php

namespace App\Services\Admin;

use App\Enums\NoContactStatus;
use App\Enums\SessionStatus;
use App\Models\NoContactProtocol;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\User;
use Illuminate\Support\Carbon;

class AdminDashboardService
{
    /**
     * @return array{
     *     stats: array<string, int>,
     *     growth: array{users_today: int, sessions_today: int},
     *     recent_users: list<array{id: int, name: string, email: string, roles: list<string>, created_at: string}>,
     *     recent_sessions: list<array{uuid: string, status: string, quiz_name: string, user_label: string, updated_at: string}>
     * }
     */
    public function snapshot(): array
    {
        $today = Carbon::today();

        return [
            'stats' => [
                'users_total' => User::query()->count(),
                'users_verified' => User::query()->whereNotNull('email_verified_at')->count(),
                'quizzes_total' => Quiz::query()->count(),
                'sessions_total' => QuizSession::query()->count(),
                'sessions_completed' => QuizSession::query()->where('status', SessionStatus::Completed)->count(),
                'sessions_in_progress' => QuizSession::query()->where('status', SessionStatus::InProgress)->count(),
                'ghost_mode_active' => NoContactProtocol::query()->where('status', NoContactStatus::Active)->count(),
                'ghost_mode_completed' => NoContactProtocol::query()->where('status', NoContactStatus::Completed)->count(),
            ],
            'growth' => [
                'users_today' => User::query()->whereDate('created_at', '>=', $today)->count(),
                'sessions_today' => QuizSession::query()->whereDate('created_at', '>=', $today)->count(),
            ],
            'recent_users' => $this->recentUsers(),
            'recent_sessions' => $this->recentSessions(),
        ];
    }

    /**
     * @return list<array{id: int, name: string, email: string, roles: list<string>, created_at: string}>
     */
    private function recentUsers(): array
    {
        return User::query()
            ->with('roles')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->all(),
                'created_at' => $user->created_at?->toIso8601String() ?? '',
            ])
            ->all();
    }

    /**
     * @return list<array{uuid: string, status: string, quiz_name: string, user_label: string, updated_at: string}>
     */
    private function recentSessions(): array
    {
        return QuizSession::query()
            ->with(['quiz', 'user'])
            ->latest('updated_at')
            ->limit(8)
            ->get()
            ->map(function (QuizSession $session): array {
                $quizName = $session->quiz?->getTranslation('name', 'en', true) ?? '—';
                $userLabel = $session->user?->email ?? $session->email ?? __('admin.anonymous');

                return [
                    'uuid' => $session->uuid,
                    'status' => $session->status->value,
                    'quiz_name' => (string) $quizName,
                    'user_label' => $userLabel,
                    'updated_at' => $session->updated_at?->toIso8601String() ?? '',
                ];
            })
            ->all();
    }
}
