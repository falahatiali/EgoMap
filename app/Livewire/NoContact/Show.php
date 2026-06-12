<?php

namespace App\Livewire\NoContact;

use App\Enums\GhostModeEventType;
use App\Enums\NoContactStatus;
use App\Models\NoContactProtocol;
use App\Services\Gamification\GamificationSlipHandler;
use App\Services\NoContact\GhostModeDailyService;
use App\Services\NoContact\NoContactTimerService;
use App\Services\NoContact\PremiumUpsellService;
use App\Services\Recovery\GhostModeAiService;
use App\Services\Recovery\GhostModeAlchemyService;
use App\Services\Recovery\GhostModeGamificationBridge;
use App\Support\GhostMode\GhostModeClient;
use App\Support\LocaleConfig;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\GamificationEngine\Enums\GamificationEvent;
use Modules\GamificationEngine\Models\GamificationBadge;
use Modules\GamificationEngine\Models\GamificationPerk;
use Modules\GamificationEngine\Services\GamificationEngine;
use Modules\GamificationEngine\Services\GamificationPunishmentService;
use Modules\GamificationEngine\Services\GamificationWalletResolver;
use Modules\GamificationEngine\Support\GamificationRewardPresenter;

#[Layout('layouts.app')]
class Show extends Component
{
    public int $selectedDays = 0;

    public bool $showSetup = false;

    public string $blackholeDraft = '';

    public ?array $blackholeResult = null;

    public bool $showSlipForm = false;

    public bool $showSlipPunishmentPicker = false;

    public string $slipTrigger = '';

    public string $lastSlipTrigger = '';

    public ?int $selectedPunishmentId = null;

    /** @var list<array<string, mixed>> */
    public array $punishmentChoices = [];

    /** @var array<string, mixed>|null */
    public ?array $pendingPunishment = null;

    public ?array $emergencySupport = null;

    public bool $showEmergencyPanel = false;

    public bool $emergencyBreathingComplete = false;

    public bool $panicBreathingSuccess = false;

    public bool $showPanicChallenge = false;

    public bool $panicChallengeDone = false;

    public string $blackholePhase = 'draft';

    public bool $destroyRewriteNext = false;

    /** @var array{writes_total: int, tier: int, tier_progress: int, streak_days: int} */
    public array $blackholeProgress = [
        'writes_total' => 0,
        'tier' => 0,
        'tier_progress' => 0,
        'streak_days' => 0,
    ];

    /** @var list<string> */
    public array $truthFlashes = [];

    public int $truthFlashIndex = 0;

    /** @var list<array<string, mixed>> */
    public array $rewardToasts = [];

    /** @var array<string, mixed> */
    public array $wallet = [];

    public bool $showBadgeGallery = false;

    public bool $showShop = false;

    public bool $showProtocolCelebration = false;

    public bool $showLevelUpToast = false;

    public int $levelUpLevel = 0;

    public string $levelUpNarrative = '';

    public ?string $confirmPerkSlug = null;

    public bool $showPremiumUpsell = false;

    /** @var array<string, mixed>|null */
    public ?array $premiumUpsell = null;

    public function mount(
        GhostModeClient $ghostMode,
        NoContactTimerService $timerService,
    ): void {
        $payload = $ghostMode->bootstrap();

        $this->selectedDays = (int) ($payload['timer']['recommended_days'] ?? $timerService->recommendedDays());
        $this->wallet = $payload['wallet'];
        $this->truthFlashes = $payload['truth_flashes'];
        $this->blackholeProgress = $payload['blackhole_progress'];
        $this->pendingPunishment = $payload['pending_punishment'];
        $this->showProtocolCelebration = (bool) ($payload['protocol_celebration'] ?? false);
        $this->premiumUpsell = $payload['premium_upsell'];
        $this->showPremiumUpsell = (bool) (is_array($payload['premium_upsell'] ?? null)
            ? ($payload['premium_upsell']['show'] ?? false)
            : false);

        foreach ($payload['gamification_events'] as $event) {
            $this->handleDispatchResult($event);
        }
    }

    public function startProtocol(GhostModeClient $ghostMode): void
    {
        try {
            $payload = $ghostMode->startProtocol($this->selectedDays);
            $this->showSetup = false;
            $this->wallet = $payload['wallet'];
            $this->truthFlashes = $payload['truth_flashes'];

            foreach ($payload['gamification_events'] as $event) {
                $this->handleDispatchResult($event);
            }

            $this->redirect(
                route('no-contact', LocaleConfig::routeParameters()),
                navigate: false,
            );
        } catch (InvalidArgumentException) {
            $this->addError('duration', __('no_contact.invalid_duration'));
        }
    }

    public function openEmergency(GhostModeAiService $ghostAi, NoContactTimerService $timerService): void
    {
        $this->emergencyBreathingComplete = false;
        $this->panicBreathingSuccess = false;
        $this->showPanicChallenge = false;
        $this->panicChallengeDone = false;
        $this->emergencySupport = $ghostAi->emergencyMessage(
            auth()->user(),
            $timerService->findActiveProtocol(),
        );
        $this->showEmergencyPanel = true;
    }

    public function closeEmergency(): void
    {
        $this->showEmergencyPanel = false;
        $this->emergencyBreathingComplete = false;
        $this->panicBreathingSuccess = false;
        $this->showPanicChallenge = false;
        $this->panicChallengeDone = false;
    }

    public function reportBreathingSuccess(bool $success): void
    {
        $this->panicBreathingSuccess = $success;
    }

    public function completeEmergency(
        GhostModeGamificationBridge $gamificationBridge,
        NoContactTimerService $timerService,
    ): void {
        $protocol = $timerService->findActiveProtocol();

        $this->handleDispatchResult($gamificationBridge->dispatchEmergencyCompleted(
            $this->actorContext(),
            $protocol,
        ));

        $this->handleDispatchResult(app(GamificationEngine::class)->dispatch(
            GamificationEvent::GhostModePanicUsed->value,
            $this->actorContext(),
        ));

        if ($this->panicBreathingSuccess) {
            $this->handleDispatchResult($gamificationBridge->dispatchPanicChallenge(
                $this->actorContext(),
                $protocol,
                true,
                false,
            ));
        }

        $this->emergencyBreathingComplete = true;
        $this->showPanicChallenge = ! $this->panicChallengeDone;
    }

    public function completePanicChallenge(
        GhostModeGamificationBridge $gamificationBridge,
        NoContactTimerService $timerService,
    ): void {
        $this->panicChallengeDone = true;
        $this->showPanicChallenge = false;

        $this->handleDispatchResult($gamificationBridge->dispatchPanicChallenge(
            $this->actorContext(),
            $timerService->findActiveProtocol(),
            $this->panicBreathingSuccess,
            true,
        ));
    }

    public function analyzeBlackhole(
        GhostModeAiService $ghostAi,
        NoContactTimerService $timerService,
        GhostModeGamificationBridge $gamificationBridge,
    ): void {
        $this->validate([
            'blackholeDraft' => ['required', 'string', 'min:3', 'max:5000'],
        ]);

        $this->blackholeResult = $ghostAi->analyzeBlackhole(
            $this->blackholeDraft,
            auth()->user(),
            $timerService->findActiveProtocol(),
        );

        $regret = (int) ($this->blackholeResult['regret_probability'] ?? 0);
        $this->handleDispatchResult($gamificationBridge->afterBlackholeAnalyzed(
            $this->actorContext(),
            $regret,
        ));

        $this->blackholePhase = 'analyzed';
        $this->destroyRewriteNext = false;
    }

    public function confirmDestroyBlackhole(
        GhostModeGamificationBridge $gamificationBridge,
    ): void {
        if ($this->blackholePhase !== 'analyzed') {
            return;
        }

        foreach ($gamificationBridge->afterBlackholeDestroyed(
            $this->actorContext(),
            $this->destroyRewriteNext,
        ) as $result) {
            $this->handleDispatchResult($result);
        }

        $this->blackholeDraft = '';
        $this->blackholePhase = 'draft';
        $this->destroyRewriteNext = false;
        $this->blackholeProgress = $gamificationBridge->blackholeProgress(
            auth()->user(),
            $this->guestToken(),
        );
    }

    public function destroyRewriteVersion(GhostModeGamificationBridge $gamificationBridge): void
    {
        $this->destroyRewriteNext = true;
        $this->confirmDestroyBlackhole($gamificationBridge);
    }

    public function destroyBlackholeDirect(
        GhostModeAiService $ghostAi,
        NoContactTimerService $timerService,
        GhostModeGamificationBridge $gamificationBridge,
    ): void {
        $this->validate([
            'blackholeDraft' => ['required', 'string', 'min:3', 'max:5000'],
        ]);

        $ghostAi->analyzeBlackhole(
            $this->blackholeDraft,
            auth()->user(),
            $timerService->findActiveProtocol(),
        );

        $this->blackholeResult = null;
        $this->blackholePhase = 'draft';

        foreach ($gamificationBridge->afterBlackholeDestroyed($this->actorContext(), false) as $result) {
            $this->handleDispatchResult($result);
        }

        $this->blackholeDraft = '';
        $this->blackholeProgress = $gamificationBridge->blackholeProgress(
            auth()->user(),
            $this->guestToken(),
        );
    }

    public function clearBlackholeResult(): void
    {
        $this->blackholeResult = null;
        $this->blackholePhase = 'draft';
        $this->destroyRewriteNext = false;
    }

    public function acceptAlchemyAndDestroy(
        GhostModeGamificationBridge $gamificationBridge,
        GhostModeAlchemyService $alchemy,
    ): void {
        if ($this->blackholePhase !== 'analyzed' || ! is_array($this->blackholeResult)) {
            return;
        }

        $commitment = trim((string) ($this->blackholeResult['commitment_suggestion'] ?? ''));

        if ($commitment !== '') {
            $alchemy->storePendingCommitment($this->actorContext(), $commitment);
        }

        $this->confirmDestroyBlackhole($gamificationBridge);
    }

    public function completeAlchemyCommitment(GhostModeAlchemyService $alchemy): void
    {
        $result = $alchemy->completePending($this->actorContext());

        if ($result === null) {
            $this->addError('alchemy', __('gamification.alchemy.none_pending'));

            return;
        }

        $this->handleDispatchResult($result);
    }

    public function beginSlipTriage(): void
    {
        $this->showSlipForm = true;
    }

    public function cancelSlipTriage(): void
    {
        $this->showSlipForm = false;
        $this->showSlipPunishmentPicker = false;
        $this->slipTrigger = '';
        $this->lastSlipTrigger = '';
        $this->punishmentChoices = [];
        $this->selectedPunishmentId = null;
    }

    public function recordSlip(
        NoContactTimerService $timerService,
        GhostModeAiService $ghostAi,
        GamificationSlipHandler $slipHandler,
    ): void {
        $triggers = config('recovery_ai.slip_triggers', []);

        $this->validate([
            'slipTrigger' => ['required', 'string', Rule::in($triggers)],
        ]);

        try {
            $protocol = $timerService->recordSlip($this->slipTrigger);

            $ghostAi->logEvent(
                $protocol,
                GhostModeEventType::Slip,
                $this->slipTrigger,
                null,
                [
                    'recovery_task' => LocaleConfig::translate('recovery_ai.slip.recovery_task'),
                ],
            );

            $slipResult = $slipHandler->record(
                $this->slipTrigger,
                $this->actorContext(['trigger' => $this->slipTrigger]),
            );

            $this->handleDispatchResult($slipResult);

            if (auth()->check()) {
                app(PremiumUpsellService::class)->resetForNewStreak(auth()->user());

                $choices = $slipResult['suggested_punishments'] ?? [];
                if ($choices !== []) {
                    $this->lastSlipTrigger = $this->slipTrigger;
                    $this->punishmentChoices = $choices;
                    $this->showSlipPunishmentPicker = true;
                    $this->selectedPunishmentId = null;
                }
            }

            $this->showPremiumUpsell = false;
            $this->premiumUpsell = null;
            $this->showSlipForm = false;
            $this->slipTrigger = '';
        } catch (InvalidArgumentException) {
            $this->showSlipForm = false;
            $this->addError('slip', __('no_contact.no_active_protocol'));
        }
    }

    public function dismissRewardToast(int $index): void
    {
        unset($this->rewardToasts[$index]);
        $this->rewardToasts = array_values($this->rewardToasts);
    }

    public function dismissLevelUpToast(): void
    {
        $this->showLevelUpToast = false;
    }

    public function dismissProtocolCelebration(): void
    {
        $this->showProtocolCelebration = false;
    }

    public function openBadgeGallery(): void
    {
        $this->showBadgeGallery = true;
    }

    public function closeBadgeGallery(): void
    {
        $this->showBadgeGallery = false;
    }

    public function toggleShop(): void
    {
        $this->showShop = ! $this->showShop;
    }

    public function promptConsumePerk(string $perkSlug): void
    {
        $this->confirmPerkSlug = $perkSlug;
    }

    public function cancelConsumePerk(): void
    {
        $this->confirmPerkSlug = null;
    }

    public function consumePerk(
        string $perkSlug,
        GamificationEngine $gamification,
        NoContactTimerService $timerService,
    ): void {
        $result = $gamification->consumePerk($perkSlug, $this->actorContext());

        if (! ($result['success'] ?? false)) {
            $this->addError('perk', $result['message'] ?? __('gamification.perks.consume_failed'));

            return;
        }

        if ($perkSlug === 'free_shield_repair') {
            try {
                $timerService->repairShield(10);
            } catch (InvalidArgumentException) {
                $this->addError('perk', __('no_contact.no_active_protocol'));
            }
        }

        $this->confirmPerkSlug = null;
        $this->syncWalletFromPayload($result['wallet'] ?? []);
    }

    public function purchaseShopItem(
        string $itemSlug,
        GamificationEngine $gamification,
        NoContactTimerService $timerService,
    ): void {
        $result = $gamification->purchaseShopItem($itemSlug, $this->actorContext());

        if (! ($result['success'] ?? false)) {
            $this->addError('shop', $result['message'] ?? __('gamification.shop.purchase_failed'));

            return;
        }

        $external = is_array($result['external_action'] ?? null) ? $result['external_action'] : null;

        if (($external['type'] ?? null) === 'shield_repair') {
            try {
                $timerService->repairShield((int) ($external['percent'] ?? 10));
            } catch (InvalidArgumentException) {
                // Item purchased; timer effect skipped when no active protocol.
            }
        }

        $this->syncWalletFromPayload($result['wallet'] ?? []);
    }

    public function nextTruthFlash(): void
    {
        if ($this->truthFlashIndex < count($this->truthFlashes) - 1) {
            $this->truthFlashIndex++;
        }
    }

    public function restartAfterComplete(NoContactTimerService $timerService): void
    {
        $this->selectedDays = $timerService->recommendedDays();
        $this->showSetup = true;
        $this->showSlipForm = false;
    }

    public function chooseSlipPunishment(
        GamificationPunishmentService $punishmentService,
        NoContactTimerService $timerService,
    ): void {
        $user = auth()->user();

        $this->validate([
            'selectedPunishmentId' => ['required', 'integer', 'exists:gamification_punishments,id'],
        ]);

        if ($user === null || $this->lastSlipTrigger === '') {
            return;
        }

        try {
            $record = $punishmentService->assign(
                $user,
                (int) $this->selectedPunishmentId,
                $timerService->findActiveProtocol(),
                $this->lastSlipTrigger,
            );

            $this->pendingPunishment = $punishmentService->userPunishmentPayload($record);
            $this->showSlipPunishmentPicker = false;
            $this->punishmentChoices = [];
            $this->lastSlipTrigger = '';
            $this->selectedPunishmentId = null;
        } catch (InvalidArgumentException $exception) {
            $this->addError('selectedPunishmentId', $exception->getMessage());
        }
    }

    public function completePendingPunishment(GamificationPunishmentService $punishmentService): void
    {
        $user = auth()->user();

        if ($user === null) {
            return;
        }

        $pending = $punishmentService->pendingFor($user->id);

        if ($pending === null) {
            $this->pendingPunishment = null;

            return;
        }

        $outcome = $punishmentService->complete($pending);
        $this->handleDispatchResult($outcome['gamification']);
        $this->pendingPunishment = null;
    }

    public function completeDailyMission(
        GamificationEngine $gamification,
        GhostModeDailyService $daily,
        GamificationWalletResolver $wallets,
    ): void {
        if (! auth()->check() || $daily->missionCompletedToday($this->wallet)) {
            return;
        }

        $this->handleDispatchResult($gamification->dispatch(
            GamificationEvent::GhostModeMissionCompleted->value,
            $this->actorContext(),
        ));

        $wallet = $wallets->resolve(auth()->id(), null);
        $meta = is_array($wallet->metadata) ? $wallet->metadata : [];
        $meta['ghost_daily_mission_date'] = now()->toDateString();
        $wallet->metadata = $meta;
        $wallet->save();
        $this->refreshWallet($gamification);
    }

    public function confirmBlockEx(
        GamificationEngine $gamification,
        GhostModeDailyService $daily,
        GamificationWalletResolver $wallets,
    ): void {
        if (! auth()->check() || $daily->blockConfirmedToday($this->wallet)) {
            return;
        }

        $this->handleDispatchResult($gamification->dispatch(
            GamificationEvent::GhostModeBlockConfirmed->value,
            $this->actorContext(),
        ));

        $wallet = $wallets->resolve(auth()->id(), null);
        $meta = is_array($wallet->metadata) ? $wallet->metadata : [];
        $meta['ghost_block_confirmed_date'] = now()->toDateString();
        $wallet->metadata = $meta;
        $wallet->save();
        $this->refreshWallet($gamification);
    }

    public function deferPremiumUpsell(
        PremiumUpsellService $upsellService,
        NoContactTimerService $timerService,
    ): void {
        $user = auth()->user();

        if ($user === null) {
            return;
        }

        $upsellService->defer($user, $timerService->findActiveProtocol());
        $this->showPremiumUpsell = false;
        $this->premiumUpsell = null;
    }

    public function redirectToCheckout(
        PremiumUpsellService $upsellService,
        NoContactTimerService $timerService,
    ) {
        $user = auth()->user();

        if ($user === null) {
            return $this->redirect(route('home', LocaleConfig::routeParameters()), navigate: false);
        }

        $protocol = $timerService->findActiveProtocol();
        $cleanStreak = (int) ($this->wallet['streak_days'] ?? 0);
        $offer = $upsellService->resolve($user, $protocol, $cleanStreak);
        $url = $offer['checkout_url'] ?? route('home', LocaleConfig::routeParameters()).'#pricing';

        $upsellService->markCheckoutClicked($user, $protocol);
        $this->showPremiumUpsell = false;
        $this->premiumUpsell = null;

        return $this->redirect($url, navigate: false);
    }

    public function render(
        NoContactTimerService $timerService,
        GamificationEngine $gamification,
        GhostModeGamificationBridge $gamificationBridge,
        GhostModeAlchemyService $alchemy,
        PremiumUpsellService $premiumUpsellService,
        GhostModeDailyService $ghostDaily,
    ): View {
        $state = $timerService->displayState();

        if ($this->showSetup && ($state['mode'] ?? '') === 'completed') {
            $state['mode'] = 'setup';
        }

        $slipPreview = $this->slipTrigger !== ''
            ? $gamification->preview(GamificationEvent::GhostModeSlipReported->value, $this->actorContext(['trigger' => $this->slipTrigger]))
            : [];

        $slipNet = $this->slipTrigger !== ''
            ? $gamification->previewSlipNet($this->actorContext(['trigger' => $this->slipTrigger]))
            : ['points' => 0, 'coins' => 0, 'xp' => 0];

        $freezeCharges = (int) ($this->wallet['metadata']['streak_freeze_charges'] ?? 0);

        $premiumUpsell = null;
        $user = auth()->user();

        if ($user !== null && ($state['mode'] ?? '') === 'active') {
            $premiumUpsell = $premiumUpsellService->resolve(
                $user,
                $timerService->findActiveProtocol(),
                (int) ($this->wallet['streak_days'] ?? 0),
            );
        }

        $this->showPremiumUpsell = (bool) ($premiumUpsell['show'] ?? false);
        $this->premiumUpsell = $premiumUpsell;

        return view('livewire.no-contact.show', [
            'state' => $state,
            'presets' => $timerService->presets(),
            'slipTriggers' => config('recovery_ai.slip_triggers', []),
            'slipPreview' => $slipPreview,
            'slipNet' => $slipNet,
            'activityFeed' => $gamification->recentTransactions(auth()->user(), 10),
            'dailyQuote' => $ghostDaily->dailyQuote(),
            'gentleMissedCheckin' => $ghostDaily->gentleMissedCheckinMessage($this->wallet),
            'dailyMissionDone' => $ghostDaily->missionCompletedToday($this->wallet),
            'blockConfirmedToday' => $ghostDaily->blockConfirmedToday($this->wallet),
            'pendingPunishment' => $this->pendingPunishment,
            'shopItems' => $gamification->activeShopItems(),
            'badgeCatalog' => GamificationBadge::query()->where('is_active', true)->orderBy('name')->get(),
            'perkCatalog' => GamificationPerk::query()->where('is_active', true)->orderBy('name')->get()->keyBy('slug'),
            'streakFreezeCharges' => $freezeCharges,
            'blackholeProgress' => $gamificationBridge->blackholeProgress(
                auth()->user(),
                $this->guestToken(),
            ),
            'hasVoicePerk' => in_array('emergency_voice_message', $this->wallet['perks'] ?? [], true),
            'pendingAlchemy' => $alchemy->pendingFor(auth()->id(), $this->guestToken()),
            'hasSlipDiscountPerk' => in_array('slip_discount_50', $this->wallet['perks'] ?? [], true),
            'premiumUpsell' => $premiumUpsell,
        ]);
    }

    private function maybeDispatchDailyLogin(GamificationEngine $gamification): void
    {
        $wallet = $gamification->walletFor(auth()->user(), $this->guestToken());
        $today = now()->toDateString();

        if (($wallet['last_login_date'] ?? null) === $today) {
            return;
        }

        $this->handleDispatchResult($gamification->dispatch(GamificationEvent::GhostModeDailyLogin->value, $this->actorContext()));
        $this->handleDispatchResult($gamification->dispatch(GamificationEvent::GhostModeDailyCheckin->value, $this->actorContext()));
    }

    private function refreshPendingPunishment(): void
    {
        if (! auth()->check()) {
            $this->pendingPunishment = null;

            return;
        }

        $pending = app(GamificationPunishmentService::class)->pendingFor(auth()->id());

        $this->pendingPunishment = $pending !== null
            ? app(GamificationPunishmentService::class)->userPunishmentPayload($pending)
            : null;
    }

    private function maybeDispatchProtocolComplete(
        NoContactTimerService $timerService,
        GamificationEngine $gamification,
    ): void {
        $owner = $timerService->resolveOwner();

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
            return;
        }

        $result = $gamification->dispatch(
            GamificationEvent::GhostModeProtocolCompleted->value,
            $this->actorContext(['protocol_uuid' => $protocol->uuid]),
        );

        $protocol->update(['gamification_rewarded_at' => now()]);
        $this->showProtocolCelebration = true;
        $this->handleDispatchResult($result);
    }

    private function refreshWallet(GamificationEngine $gamification): void
    {
        $this->wallet = $gamification->walletFor(auth()->user(), $this->guestToken());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleDispatchResult(array $payload): void
    {
        $this->pushRewardToast($payload);
        $this->syncWalletFromPayload($payload['wallet'] ?? []);
    }

    /**
     * @param  array<string, mixed>  $walletPayload
     */
    private function syncWalletFromPayload(array $walletPayload): void
    {
        if ($walletPayload === []) {
            return;
        }

        $oldLevel = (int) ($this->wallet['level'] ?? 1);
        $this->wallet = $walletPayload;
        $newLevel = (int) ($this->wallet['level'] ?? 1);

        if ($newLevel > $oldLevel) {
            $this->showLevelUpToast = true;
            $this->levelUpLevel = $newLevel;
            $this->levelUpNarrative = app(GamificationRewardPresenter::class)->levelNarrative($newLevel);
        }
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array{user_id: ?int, guest_token: ?string, metadata: array<string, mixed>}
     */
    private function actorContext(array $metadata = []): array
    {
        return [
            'user_id' => auth()->id(),
            'guest_token' => auth()->check() ? null : $this->guestToken(),
            'metadata' => $metadata,
        ];
    }

    private function guestToken(): ?string
    {
        return auth()->check() ? null : request()->cookie('egomap_guest');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function pushRewardToast(array $payload): void
    {
        if (($payload['applied'] ?? []) === []) {
            return;
        }

        $formatted = app(GamificationRewardPresenter::class)->formatToast($payload);
        $this->rewardToasts[] = array_merge($payload, ['toast' => $formatted]);
    }
}
