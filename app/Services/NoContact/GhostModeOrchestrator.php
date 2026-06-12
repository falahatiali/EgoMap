<?php

namespace App\Services\NoContact;

use App\DataTransferObjects\GhostMode\GhostModeActor;
use App\Enums\NoContactStatus;
use App\Models\NoContactProtocol;
use App\Services\Recovery\GhostModeAiService;
use App\Services\Recovery\GhostModeAlchemyService;
use App\Services\Recovery\GhostModeGamificationBridge;
use App\Services\Recovery\RecoveryJourneyService;
use App\Support\LocaleConfig;
use InvalidArgumentException;
use Modules\GamificationEngine\Enums\GamificationEvent;
use Modules\GamificationEngine\Models\GamificationBadge;
use Modules\GamificationEngine\Models\GamificationPerk;
use Modules\GamificationEngine\Services\GamificationEngine;
use Modules\GamificationEngine\Services\GamificationPunishmentService;
use Modules\GamificationEngine\Support\GamificationRewardPresenter;

class GhostModeOrchestrator
{
    public function __construct(
        private readonly NoContactTimerService $timerService,
        private readonly GhostModeAiService $ghostAi,
        private readonly GhostModeGamificationBridge $gamificationBridge,
        private readonly GhostModeAlchemyService $alchemy,
        private readonly GhostModeDailyService $dailyService,
        private readonly PremiumUpsellService $upsellService,
        private readonly GamificationEngine $gamification,
        private readonly GamificationPunishmentService $punishmentService,
        private readonly RecoveryJourneyService $journey,
        private readonly GamificationRewardPresenter $rewardPresenter,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function bootstrap(GhostModeActor $actor, ?string $locale = null): array
    {
        $locale = LocaleConfig::resolve($locale ?? app()->getLocale());
        $owner = $actor->toOwner();

        $this->journey->markNoContactActivated();

        $wallet = $this->gamification->walletFor($actor->user, $actor->guestToken);
        $state = $this->timerService->displayStateFor($owner, $locale);
        $protocol = $this->timerService->findActiveProtocolFor($owner);

        $gamificationEvents = [];
        $protocolCelebration = false;

        if ($protocol !== null && ($state['mode'] ?? '') === 'active') {
            $gamificationEvents = array_merge(
                $gamificationEvents,
                $this->maybeDispatchDailyLogin($actor, $wallet),
            );
        }

        $protocolComplete = $this->maybeDispatchProtocolComplete($actor);
        if ($protocolComplete !== null) {
            $gamificationEvents[] = $protocolComplete;
            $protocolCelebration = true;
            $wallet = $protocolComplete['wallet'] ?? $wallet;
        }

        foreach ($gamificationEvents as $event) {
            if (isset($event['wallet']) && is_array($event['wallet'])) {
                $wallet = $event['wallet'];
            }
        }

        return $this->present(
            $actor,
            $state,
            $wallet,
            $locale,
            $gamificationEvents,
            $protocolCelebration,
        );
    }

    /**
     * Mobile API bootstrap — timer, wallet, copy, and truth flashes only.
     * Skips web-only catalogs and feeds so activation feels instant like the client timer on web.
     *
     * @return array<string, mixed>
     */
    public function bootstrapForApi(GhostModeActor $actor, ?string $locale = null): array
    {
        $locale = LocaleConfig::resolve($locale ?? app()->getLocale());
        $owner = $actor->toOwner();

        $this->journey->markNoContactActivated();

        $wallet = $this->gamification->walletFor($actor->user, $actor->guestToken);
        $state = $this->timerService->displayStateFor($owner, $locale);
        $protocol = $this->timerService->findActiveProtocolFor($owner);

        $gamificationEvents = [];
        $protocolCelebration = false;

        if ($protocol !== null && ($state['mode'] ?? '') === 'active') {
            $gamificationEvents = array_merge(
                $gamificationEvents,
                $this->maybeDispatchDailyLogin($actor, $wallet),
            );
        }

        $protocolComplete = $this->maybeDispatchProtocolComplete($actor);
        if ($protocolComplete !== null) {
            $gamificationEvents[] = $protocolComplete;
            $protocolCelebration = true;
            $wallet = $protocolComplete['wallet'] ?? $wallet;
        }

        foreach ($gamificationEvents as $event) {
            if (isset($event['wallet']) && is_array($event['wallet'])) {
                $wallet = $event['wallet'];
            }
        }

        return $this->presentForApi(
            $actor,
            $state,
            $wallet,
            $locale,
            $gamificationEvents,
            $protocolCelebration,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function startProtocol(GhostModeActor $actor, int $durationDays, ?string $locale = null): array
    {
        $locale = LocaleConfig::resolve($locale ?? app()->getLocale());
        $owner = $actor->toOwner();

        if ($owner['guest_token'] === null && $owner['user_id'] === null) {
            throw new InvalidArgumentException('Guest token is required to start Ghost Mode.');
        }

        $this->timerService->startFor($owner, $durationDays);

        $activated = $this->gamification->dispatch(
            GamificationEvent::GhostModeActivated->value,
            $actor->gamificationContext(),
        );

        $wallet = is_array($activated['wallet'] ?? null) ? $activated['wallet'] : $this->gamification->walletFor(
            $actor->user,
            $actor->guestToken,
        );

        $state = $this->timerService->displayStateFor($owner, $locale);

        return $this->present($actor, $state, $wallet, $locale, [$activated], false);
    }

    /**
     * Mobile API activate — same protocol start as web, lite response payload.
     *
     * @return array<string, mixed>
     */
    public function startProtocolForApi(GhostModeActor $actor, int $durationDays, ?string $locale = null): array
    {
        $locale = LocaleConfig::resolve($locale ?? app()->getLocale());
        $owner = $actor->toOwner();

        if ($owner['guest_token'] === null && $owner['user_id'] === null) {
            throw new InvalidArgumentException('Guest token is required to start Ghost Mode.');
        }

        $this->timerService->startFor($owner, $durationDays);

        $activated = $this->gamification->dispatch(
            GamificationEvent::GhostModeActivated->value,
            $actor->gamificationContext(),
        );

        $wallet = is_array($activated['wallet'] ?? null) ? $activated['wallet'] : $this->gamification->walletFor(
            $actor->user,
            $actor->guestToken,
        );

        $state = $this->timerService->displayStateFor($owner, $locale);

        return $this->presentForApi($actor, $state, $wallet, $locale, [$activated], false);
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  array<string, mixed>  $wallet
     * @param  list<array<string, mixed>>  $gamificationEvents
     * @return array<string, mixed>
     */
    private function presentForApi(
        GhostModeActor $actor,
        array $state,
        array $wallet,
        string $locale,
        array $gamificationEvents = [],
        bool $protocolCelebration = false,
    ): array {
        $protocol = $this->timerService->findActiveProtocolFor($actor->toOwner());
        $truthFlashes = $protocol !== null && ($state['mode'] ?? '') === 'active'
            ? $this->ghostAi->truthFlashes($actor->user, $protocol)
            : [];

        return [
            'locale' => $locale,
            'is_authenticated' => $actor->isAuthenticated(),
            'timer' => $state,
            'wallet' => $wallet,
            'truth_flashes' => $truthFlashes,
            'copy' => $this->copyFor($locale, (string) ($state['mode'] ?? 'setup')),
            'gamification_events' => array_map(
                fn (array $event): array => $this->formatGamificationEvent($event),
                array_values(array_filter($gamificationEvents)),
            ),
            'protocol_celebration' => $protocolCelebration,
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  array<string, mixed>  $wallet
     * @param  list<array<string, mixed>>  $gamificationEvents
     * @return array<string, mixed>
     */
    private function present(
        GhostModeActor $actor,
        array $state,
        array $wallet,
        string $locale,
        array $gamificationEvents = [],
        bool $protocolCelebration = false,
    ): array {
        $protocol = $this->timerService->findActiveProtocolFor($actor->toOwner());
        $truthFlashes = $protocol !== null && ($state['mode'] ?? '') === 'active'
            ? $this->ghostAi->truthFlashes($actor->user, $protocol)
            : [];

        $premiumUpsell = null;
        if ($actor->isAuthenticated() && ($state['mode'] ?? '') === 'active') {
            $premiumUpsell = $this->upsellService->resolve(
                $actor->user,
                $protocol,
                (int) ($wallet['streak_days'] ?? 0),
            );
        }

        $pendingPunishment = null;
        if ($actor->isAuthenticated()) {
            $pending = $this->punishmentService->pendingFor($actor->user->id);
            $pendingPunishment = $pending !== null
                ? $this->punishmentService->userPunishmentPayload($pending)
                : null;
        }

        $freezeCharges = (int) ($wallet['metadata']['streak_freeze_charges'] ?? 0);
        $perks = $wallet['perks'] ?? [];

        return [
            'locale' => $locale,
            'is_authenticated' => $actor->isAuthenticated(),
            'timer' => $state,
            'wallet' => $wallet,
            'truth_flashes' => $truthFlashes,
            'copy' => $this->copyFor($locale, (string) ($state['mode'] ?? 'setup')),
            'blackhole_progress' => $this->gamificationBridge->blackholeProgress(
                $actor->user,
                $actor->guestToken,
            ),
            'pending_alchemy' => $this->alchemy->pendingFor($actor->user?->id, $actor->guestToken),
            'daily' => [
                'quote' => $this->dailyService->dailyQuote(),
                'mission_completed_today' => $this->dailyService->missionCompletedToday($wallet),
                'block_confirmed_today' => $this->dailyService->blockConfirmedToday($wallet),
                'gentle_missed_checkin' => $this->dailyService->gentleMissedCheckinMessage($wallet),
            ],
            'slip_triggers' => config('recovery_ai.slip_triggers', []),
            'activity_feed' => $this->gamification->recentTransactions($actor->user, 10),
            'shop_items' => $this->gamification->activeShopItems(),
            'badge_catalog' => GamificationBadge::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'slug', 'name', 'description', 'icon'])
                ->all(),
            'perk_catalog' => GamificationPerk::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'slug', 'name', 'description', 'icon'])
                ->all(),
            'pending_punishment' => $pendingPunishment,
            'premium_upsell' => $premiumUpsell,
            'streak_freeze_charges' => $freezeCharges,
            'has_voice_perk' => in_array('emergency_voice_message', $perks, true),
            'has_slip_discount_perk' => in_array('slip_discount_50', $perks, true),
            'gamification_events' => array_map(
                fn (array $event): array => $this->formatGamificationEvent($event),
                array_values(array_filter($gamificationEvents)),
            ),
            'protocol_celebration' => $protocolCelebration,
        ];
    }

    /**
     * @param  array<string, mixed>  $wallet
     * @return list<array<string, mixed>>
     */
    private function maybeDispatchDailyLogin(GhostModeActor $actor, array $wallet): array
    {
        $today = now()->toDateString();

        if (($wallet['last_login_date'] ?? null) === $today) {
            return [];
        }

        return [
            $this->gamification->dispatch(
                GamificationEvent::GhostModeDailyLogin->value,
                $actor->gamificationContext(),
            ),
            $this->gamification->dispatch(
                GamificationEvent::GhostModeDailyCheckin->value,
                $actor->gamificationContext(),
            ),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function maybeDispatchProtocolComplete(GhostModeActor $actor): ?array
    {
        $owner = $actor->toOwner();

        $protocol = NoContactProtocol::query()
            ->when(
                $owner['user_id'] !== null,
                fn ($query) => $query->where('user_id', $owner['user_id']),
                fn ($query) => $query->where('guest_token', $owner['guest_token']),
            )
            ->where('status', NoContactStatus::Completed)
            ->whereNull('gamification_rewarded_at')
            ->latest('completed_at')
            ->first();

        if ($protocol === null) {
            return null;
        }

        $result = $this->gamification->dispatch(
            GamificationEvent::GhostModeProtocolCompleted->value,
            $actor->gamificationContext(['protocol_uuid' => $protocol->uuid]),
        );

        $protocol->update(['gamification_rewarded_at' => now()]);

        return $result;
    }

    /**
     * @return array<string, string>
     */
    private function copyFor(string $locale, string $mode): array
    {
        $copy = [
            'page_title' => __('no_contact.page_title', locale: $locale),
            'page_subtitle' => __('no_contact.page_subtitle', locale: $locale),
            'setup_badge' => __('no_contact.setup_badge', locale: $locale),
            'setup_title' => __('no_contact.setup_title', locale: $locale),
            'setup_subtitle' => __('no_contact.setup_subtitle', locale: $locale),
            'recommended' => __('no_contact.recommended', locale: $locale),
            'start_protocol' => __('no_contact.start_protocol', locale: $locale),
            'status_not_started' => __('no_contact.status_not_started', locale: $locale),
            'status_active' => __('no_contact.status_active', locale: $locale),
            'shield_title' => __('no_contact.shield_title', locale: $locale),
            'stat_elapsed' => __('no_contact.stat_elapsed', locale: $locale),
            'remaining_label' => __('no_contact.remaining_label', locale: $locale),
            'unit_days' => __('no_contact.unit_days', locale: $locale),
            'unit_hours' => __('no_contact.unit_hours', locale: $locale),
            'unit_minutes' => __('no_contact.unit_minutes', locale: $locale),
            'unit_seconds' => __('no_contact.unit_seconds', locale: $locale),
            'emergency_button' => __('no_contact.emergency_button', locale: $locale),
            'truth_title' => __('no_contact.truth_title', locale: $locale),
            'truth_subtitle' => __('no_contact.truth_subtitle', locale: $locale),
            'truth_next' => __('no_contact.truth_next', locale: $locale),
            'completed_badge' => __('no_contact.completed_badge', locale: $locale),
            'completed_title' => __('no_contact.completed_title', locale: $locale),
            'start_again' => __('no_contact.start_again', locale: $locale),
            'mobile_active_note' => __('no_contact.mobile_active_note', locale: $locale),
        ];

        if ($mode === 'completed') {
            $copy['completed_subtitle'] = __('no_contact.completed_subtitle', [
                'days' => ':days',
            ], $locale);
        }

        return $copy;
    }

    /**
     * @param  array<string, mixed>  $event
     * @return array<string, mixed>
     */
    private function formatGamificationEvent(array $event): array
    {
        if (($event['applied'] ?? []) === []) {
            return $event;
        }

        return array_merge($event, [
            'toast' => $this->rewardPresenter->formatToast($event),
        ]);
    }
}
