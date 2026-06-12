<?php

namespace App\Services\Quiz;

use App\Enums\QuestionType;
use App\Enums\SessionStatus;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Services\Quiz\RebootProtocol\RebootProtocolFlow;
use App\Support\LocaleConfig;
use App\Support\LocalizedNumbers;
use App\Support\QuizResultViewData;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class QuizSessionStatePresenter
{
    public function __construct(
        private readonly RebootProtocolFlow $flow,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function present(QuizSession $session, ?string $locale = null): array
    {
        $session->loadMissing([
            'quiz.questions' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')->with('options'),
            'responses',
            'result.outcomeProfile',
        ]);

        $locale = LocaleConfig::resolve($locale ?? $session->locale ?? app()->getLocale());
        $slug = $session->quiz->slug;
        $total = max($session->quiz->questions->count(), 1);
        $current = min((int) $session->current_sort_order, $total);
        $progress = (int) round(($current / $total) * 100);

        $base = [
            'session' => [
                'uuid' => $session->uuid,
                'status' => $session->status->value,
                'quiz_slug' => $slug,
                'locale' => $locale,
                'current_sort_order' => (int) $session->current_sort_order,
            ],
            'progress' => [
                'current' => $current,
                'total' => $total,
                'percent' => $progress,
            ],
        ];

        if ($this->flow->isRebootQuiz($slug) && $this->flow->isCrisis($session)) {
            return array_merge($base, [
                'screen' => 'crisis',
                'crisis' => [
                    'badge' => __('quiz.reboot.crisis_badge', locale: $locale),
                    'title' => __('quiz.reboot.crisis_title', locale: $locale),
                    'body' => __('quiz.reboot.crisis_body', locale: $locale),
                    'reset_label' => __('quiz.reboot.crisis_reset', locale: $locale),
                ],
            ]);
        }

        if ($this->flow->isRebootQuiz($slug) && $this->flow->isSafetyPending($session)) {
            return array_merge($base, [
                'screen' => 'safety',
                'safety' => [
                    'badge' => __('quiz.reboot.safety_badge', locale: $locale),
                    'title' => __('quiz.reboot.safety_title', locale: $locale),
                    'intro' => __('quiz.reboot.safety_intro', locale: $locale),
                    'options' => collect(range(1, 4))
                        ->map(fn (int $value): array => [
                            'value' => (string) $value,
                            'label' => __("quiz.reboot.safety_opt_{$value}", locale: $locale),
                        ])
                        ->all(),
                ],
            ]);
        }

        if ($session->status === SessionStatus::Completed) {
            return array_merge($base, [
                'screen' => 'result',
                'result' => $this->presentResult($session, $locale),
            ]);
        }

        $question = $this->resolveCurrentQuestion($session);

        if ($question === null) {
            return array_merge($base, [
                'screen' => 'result',
                'result' => $this->presentResult($session, $locale),
            ]);
        }

        return array_merge($base, [
            'screen' => 'question',
            'question' => $this->presentQuestion($question, $locale),
            'can_go_back' => $session->current_sort_order > 1,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function presentQuizMeta(Quiz $quiz, ?string $locale = null): array
    {
        $locale = LocaleConfig::resolve($locale ?? app()->getLocale());
        $welcome = $quiz->settings['welcome'] ?? [];

        return [
            'slug' => $quiz->slug,
            'name' => $quiz->getTranslation('name', $locale, true),
            'description' => $quiz->getTranslation('description', $locale, true),
            'estimated_minutes' => (int) $quiz->estimated_minutes,
            'question_count' => $quiz->questions()->where('is_active', true)->count(),
            'welcome' => [
                'title' => __('quiz.reboot.welcome_title', locale: $locale),
                'body' => is_array($welcome) ? (string) ($welcome[$locale] ?? $welcome['en'] ?? '') : '',
                'note' => __('quiz.reboot.welcome_note', locale: $locale),
                'begin_label' => __('quiz.reboot.begin', locale: $locale),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function presentReturning(QuizSession $session, ?string $locale = null): array
    {
        $locale = LocaleConfig::resolve($locale ?? $session->locale ?? app()->getLocale());
        $session->loadMissing(['result.outcomeProfile', 'quiz']);
        $resultData = QuizResultViewData::fromSession($session, $locale);
        $report = $resultData['report'];
        $content = $resultData['content'];

        return [
            'session_uuid' => $session->uuid,
            'quiz_name' => $session->quiz->getTranslation('name', $locale, true),
            'type_code' => (string) ($report['type_code'] ?? '—'),
            'title' => (string) ($report['title'] ?? ''),
            'summary' => (string) ($content['tagline'] ?? ($report['summary'] ?? '')),
            'completed_at' => $session->completed_at?->toIso8601String(),
            'palette' => $resultData['palette'],
            'view_result_label' => __('quiz.view_previous_result', locale: $locale),
            'retake_label' => __('quiz.retake_test', locale: $locale),
            'eyebrow' => __('quiz.returning_eyebrow', locale: $locale),
        ];
    }

    /**
     * @param  array{action: 'resume'|'show_previous'|'start_fresh', session: QuizSession|null}  $entry
     * @return array<string, mixed>
     */
    public function presentEntry(array $entry, ?string $locale = null): array
    {
        $locale = LocaleConfig::resolve($locale ?? app()->getLocale());
        $session = $entry['session'];

        $payload = [
            'action' => $entry['action'],
            'session_uuid' => $session?->uuid,
        ];

        if ($entry['action'] === 'show_previous' && $session !== null) {
            $payload['returning'] = $this->presentReturning($session, $locale);
        }

        if ($entry['action'] === 'resume' && $session !== null) {
            $payload['screen'] = $session->status === SessionStatus::Completed
                ? 'result'
                : 'question';
        }

        return $payload;
    }

    public function authorizeSessionAccess(QuizSession $session, ?string $guestToken): void
    {
        $user = auth('sanctum')->user();

        if ($user !== null && $session->user_id === $user->id) {
            return;
        }

        if ($session->user_id !== null) {
            throw new AuthorizationException('You cannot access this quiz session.');
        }

        if ($guestToken !== null && $session->guest_token === $guestToken) {
            return;
        }

        throw new AuthorizationException('You cannot access this quiz session.');
    }

    public function findAuthorizedSession(string $uuid, ?string $guestToken): QuizSession
    {
        $session = QuizSession::query()
            ->with(['quiz'])
            ->where('uuid', $uuid)
            ->first();

        if ($session === null) {
            throw new NotFoundHttpException('Quiz session not found.');
        }

        $this->authorizeSessionAccess($session, $guestToken);

        return $session;
    }

    /**
     * @return array<string, mixed>
     */
    private function presentQuestion(Question $question, string $locale): array
    {
        $config = $question->config ?? [];
        $isMultiple = $question->type === QuestionType::MultipleChoice;

        return [
            'id' => $question->id,
            'sort_order' => (int) $question->sort_order,
            'key' => (string) ($config['key'] ?? ''),
            'type' => $question->type->value,
            'text' => $question->getTranslation('text', $locale, true),
            'help_text' => $question->getTranslation('help_text', $locale, true),
            'max_selections' => $isMultiple ? (int) ($config['max_selections'] ?? 1) : 1,
            'requires_continue' => $isMultiple,
            'options' => $question->options->map(fn ($option): array => [
                'value' => (string) $option->value,
                'label' => $option->getTranslation('label', $locale, true),
                'accent' => (string) (($option->meta['accent'] ?? 'emerald')),
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
    public function presentResult(QuizSession $session, ?string $locale = null): array
    {
        $session->loadMissing(['result.outcomeProfile', 'quiz']);
        $locale = LocaleConfig::resolve($locale ?? $session->locale ?? app()->getLocale());

        $viewData = QuizResultViewData::fromSession($session, $locale);
        $report = $viewData['report'];
        $content = $viewData['content'];

        $nextSteps = [];

        foreach ($report['next_steps'] ?? [] as $step) {
            if (is_array($step)) {
                $nextSteps[] = LocalizedNumbers::format(
                    LocaleConfig::pick($step, $locale),
                    $locale,
                );
            } elseif (is_string($step) && $step !== '') {
                $nextSteps[] = $step;
            }
        }

        return [
            'template' => (string) ($report['template'] ?? ''),
            'hero_label' => (string) ($content['hero_label'] ?? __('quiz.your_result', locale: $locale)),
            'type_label' => (string) ($content['type_label'] ?? ($report['title'] ?? '')),
            'archetype' => (string) ($content['archetype'] ?? ($report['title'] ?? '')),
            'tagline' => (string) ($content['tagline'] ?? ($report['summary'] ?? '')),
            'prescription' => (string) ($content['prescription'] ?? ''),
            'disclaimer' => (string) ($content['disclaimer'] ?? ''),
            'stability_score' => $report['stability_score'] ?? null,
            'stability_title' => __('quiz.reboot.stability_title', locale: $locale),
            'stability_note' => __('quiz.reboot.stability_note', locale: $locale),
            'score_tagline' => (string) ($report['score_tagline'] ?? ''),
            'emergency' => (bool) ($report['emergency'] ?? false),
            'emergency_alert' => __('quiz.reboot.emergency_alert', locale: $locale),
            'first_prescription_title' => __('quiz.reboot.first_prescription', locale: $locale),
            'next_steps_title' => __('quiz.reboot.next_steps', locale: $locale),
            'next_steps' => $nextSteps,
            'dimension_rows' => $report['dimension_rows'] ?? [],
            'dimension_breakdown_title' => __('quiz.reboot.dimension_breakdown', locale: $locale),
            'sections' => $content['sections'] ?? [],
            'ai_insights' => $content['ai_insights'] ?? null,
            'palette' => $viewData['palette'],
            'email' => [
                'sent' => $session->email_report_sent_at !== null,
                'address' => (string) ($session->email ?? ''),
                'title' => __('quiz.full_report_title', locale: $locale),
                'description' => __('quiz.full_report_description', locale: $locale),
                'label' => __('quiz.email_label', locale: $locale),
                'placeholder' => __('quiz.email_placeholder', locale: $locale),
                'submit' => __('quiz.send_full_report', locale: $locale),
                'sending' => __('quiz.sending', locale: $locale),
                'sent_title' => __('quiz.email_sent_title', locale: $locale),
                'sent_message' => $session->email_report_sent_at !== null && $session->email
                    ? __('quiz.email_sent', ['email' => $session->email], $locale)
                    : null,
            ],
            'account_cta' => auth('sanctum')->check() ? null : [
                'title' => __('auth.create_account_cta_title', locale: $locale),
                'body' => __('auth.create_account_cta_body', locale: $locale),
                'button' => __('auth.create_account_cta_button', locale: $locale),
            ],
            'is_authenticated' => auth('sanctum')->check(),
            'profile_label' => __('profile.page_title', locale: $locale),
            'back_home_label' => __('quiz.back_home', locale: $locale),
        ];
    }

    private function resolveCurrentQuestion(QuizSession $session): ?Question
    {
        if ($session->status !== SessionStatus::InProgress) {
            return null;
        }

        return Question::query()
            ->where('quiz_id', $session->quiz_id)
            ->where('sort_order', (int) $session->current_sort_order)
            ->where('is_active', true)
            ->with('options')
            ->first();
    }
}
