<?php

namespace App\Livewire\Missions;

use App\Services\Missions\MissionAetherAdherenceService;
use App\Services\Missions\MissionAetherProgramService;
use App\Services\Profile\UserAetherProgramHistoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\AetherEngine\Enums\BodyBuild;
use Modules\AetherEngine\Enums\BodyGoal;
use Modules\AetherEngine\Enums\CoachingTone;
use Modules\AetherEngine\Enums\CookingAbility;
use Modules\AetherEngine\Enums\DietaryPattern;
use Modules\AetherEngine\Enums\EquipmentAccess;
use Modules\AetherEngine\Enums\Gender;
use Modules\AetherEngine\Enums\GymConfidence;
use Modules\AetherEngine\Enums\MotivationStyle;
use Modules\AetherEngine\Enums\PrimaryGoal;
use Modules\AetherEngine\Enums\SessionDuration;
use Modules\AetherEngine\Enums\TrainingExperience;
use Modules\AetherEngine\Enums\TrainingStylePreference;
use Modules\AetherEngine\Enums\WorkoutTimePreference;
use Modules\AetherEngine\Services\AetherProfileService;
use Modules\AetherEngine\Support\AetherWorkoutWizardSteps;
use Modules\MissionEngine\Enums\EquipmentCategory;
use Modules\MissionEngine\Enums\EquipmentStatus;
use Modules\MissionEngine\Models\MissionEnrollment;
use Modules\MissionEngine\Services\MissionDailyReportService;
use Modules\MissionEngine\Services\MissionEnrollmentFieldService;
use Modules\MissionEngine\Services\MissionSupplementLogService;
use Modules\MissionEngine\Support\MissionEnrollmentPresenter;
use Modules\MissionEngine\Support\MissionLocalizedText;
use Modules\MissionEngine\Support\MissionProGate;

#[Layout('layouts.app')]
class Workspace extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public MissionEnrollment $enrollment;

    #[Url(as: 'tab', history: true)]
    public string $activeTab = 'program';

    public string $logDate;

    /** Schedule */
    /** @var list<string> */
    public array $gymDays = [];

    public string $preferredGymTime = '18:00';

    /** Supplements */
    public string $newSupplementName = '';

    public string $newSupplementBrand = '';

    public string $newSupplementUnit = 'scoop';

    public string $newSupplementDefaultAmount = '1';

    public ?int $intakeProductId = null;

    public string $intakeProductName = '';

    public string $intakeBrand = '';

    public string $intakeAmount = '1';

    public string $intakeUnit = 'scoop';

    public string $intakeNotes = '';

    /** Daily report */
    public ?float $reportWeight = null;

    public ?int $reportMood = null;

    public ?int $reportEnergy = null;

    public ?float $reportSleep = null;

    public bool $reportTrained = false;

    public bool $reportNutritionLogged = false;

    public string $reportHighlights = '';

    public string $reportChallenges = '';

    public string $reportNotes = '';

    /**
     * @var list<array{id: string, name: string, category: string, brand: string, status: string, notes: string}>
     */
    public array $equipmentItems = [];

    public string $newEquipmentName = '';

    public string $newEquipmentCategory = 'accessories';

    public string $newEquipmentBrand = '';

    public string $newEquipmentStatus = 'owned';

    public string $newEquipmentNotes = '';

    public string $equipmentNotes = '';

    public bool $showAiQuestionnaire = false;

    public string $aiQuestionnaireTarget = 'workout';

    public int $aiWizardStep = 1;

    public int $aiAge = 28;

    public string $aiGender = 'male';

    public int $aiHeightCm = 175;

    public float $aiWeightKg = 75;

    public ?float $aiBodyFatPercent = null;

    public string $aiTrainingExperience = 'intermediate';

    public string $aiPrimaryGoal = 'muscle_gain';

    public int $aiTrainingDaysPerWeek = 4;

    public string $aiSessionDuration = '45_60';

    public string $aiPreferredWorkoutTime = 'evening';

    public string $aiEquipment = 'full_gym';

    public string $aiDietaryPattern = 'omnivore';

    public string $aiCookingAbility = 'simple';

    public string $aiCoachingTone = 'gentle';

    public string $aiMotivationStyle = 'feeling_strong';

    public string $aiTrainingStyle = 'heavy_weights';

    /** @var list<string> */
    public array $aiInjuryTags = [];

    public string $aiAgeRange = '18_29';

    public string $aiCurrentBodyBuild = '';

    public string $aiTargetBodyGoal = '';

    public string $aiGymConfidence = '';

    public bool $aiIsGenerating = false;

    /** @var array<string, bool> */
    public array $registrationProgress = [];

    public function mount(MissionEnrollment $enrollment, MissionSupplementLogService $supplements): void
    {
        abort_unless(Auth::id() === $enrollment->user_id, 403);

        $this->enrollment = $enrollment->load('template');
        $this->logDate = now()->toDateString();

        $this->hydrateFromFieldValues();
        $this->ensureDefaultSupplements($supplements);
        $this->loadLogsForDate();
        $this->ensureValidTab();
    }

    public function updatedLogDate(): void
    {
        $this->loadLogsForDate();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function saveSchedule(MissionEnrollmentFieldService $fields): void
    {
        $fields->merge($this->enrollment, [
            'gym_days' => $this->gymDays,
            'preferred_gym_time' => $this->preferredGymTime,
        ], Auth::user());

        $this->flashSaved();
    }

    public function addSupplementProduct(MissionSupplementLogService $supplements): void
    {
        $this->validate([
            'newSupplementName' => ['required', 'string', 'max:120'],
            'newSupplementBrand' => ['nullable', 'string', 'max:120'],
        ]);

        $supplements->addProduct($this->enrollment, [
            'name' => $this->newSupplementName,
            'brand' => $this->newSupplementBrand ?: null,
            'default_unit' => $this->newSupplementUnit,
            'default_amount' => $this->newSupplementDefaultAmount,
        ]);

        $this->reset(['newSupplementName', 'newSupplementBrand']);
        $this->flashSaved();
    }

    public function selectSupplementProduct(int $productId): void
    {
        $product = $this->enrollment->supplementProducts()->find($productId);

        if ($product === null) {
            return;
        }

        $this->intakeProductId = $product->id;
        $this->intakeProductName = $product->name;
        $this->intakeBrand = (string) ($product->brand ?? '');
        $this->intakeUnit = $product->default_unit;
        $this->intakeAmount = (string) ($product->default_amount ?? '1');
    }

    public function logSupplementIntake(MissionSupplementLogService $supplements): void
    {
        $this->validate([
            'logDate' => ['required', 'date'],
            'intakeProductName' => ['required', 'string', 'max:120'],
            'intakeAmount' => ['required', 'numeric', 'min:0.01', 'max:9999'],
            'intakeUnit' => ['required', 'string', 'max:32'],
        ]);

        $supplements->logIntake($this->enrollment, Auth::user(), [
            'intake_date' => $this->logDate,
            'supplement_product_id' => $this->intakeProductId,
            'product_name' => $this->intakeProductName,
            'brand' => $this->intakeBrand ?: null,
            'amount' => (float) $this->intakeAmount,
            'unit' => $this->intakeUnit,
            'notes' => $this->intakeNotes ?: null,
        ]);

        $this->intakeNotes = '';
        $this->flashSaved();
    }

    public function saveDailyReport(
        MissionDailyReportService $reports,
        MissionAetherAdherenceService $adherence,
    ): void {
        $this->validate([
            'logDate' => ['required', 'date'],
            'reportWeight' => ['nullable', 'numeric', 'min:20', 'max:400'],
            'reportMood' => ['nullable', 'integer', 'min:1', 'max:10'],
            'reportEnergy' => ['nullable', 'integer', 'min:1', 'max:10'],
            'reportSleep' => ['nullable', 'numeric', 'min:0', 'max:24'],
        ]);

        $user = Auth::user();

        $reports->save($this->enrollment, $user, [
            'report_date' => $this->logDate,
            'body_weight' => $this->reportWeight,
            'mood_score' => $this->reportMood,
            'energy_score' => $this->reportEnergy,
            'sleep_hours' => $this->reportSleep,
            'trained_today' => $this->reportTrained || $adherence->trainedOnDate($user, $this->logDate),
            'nutrition_logged' => $this->reportNutritionLogged,
            'highlights' => $this->reportHighlights ?: null,
            'challenges' => $this->reportChallenges ?: null,
            'notes' => $this->reportNotes ?: null,
        ]);

        $adherence->syncEnrollmentProgress($this->enrollment->fresh(), $user);
        $this->enrollment->refresh()->load(['measurements' => fn ($q) => $q->latest('measured_at')->limit(10)]);
        $this->flashSaved();
    }

    public function toggleRegistrationStep(string $step, MissionEnrollmentFieldService $fields): void
    {
        $this->registrationProgress[$step] = ! ($this->registrationProgress[$step] ?? false);

        $fields->merge($this->enrollment, [
            'registration_progress' => $this->registrationProgress,
        ], Auth::user());
    }

    public function addEquipmentItem(MissionEnrollmentFieldService $fields): void
    {
        $categoryValues = array_map(
            static fn (EquipmentCategory $category): string => $category->value,
            EquipmentCategory::cases(),
        );
        $statusValues = array_map(
            static fn (EquipmentStatus $status): string => $status->value,
            EquipmentStatus::cases(),
        );

        $this->validate([
            'newEquipmentName' => ['required', 'string', 'max:120'],
            'newEquipmentCategory' => ['required', Rule::in($categoryValues)],
            'newEquipmentStatus' => ['required', Rule::in($statusValues)],
            'newEquipmentBrand' => ['nullable', 'string', 'max:80'],
            'newEquipmentNotes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->equipmentItems[] = [
            'id' => (string) Str::uuid(),
            'name' => trim($this->newEquipmentName),
            'category' => $this->newEquipmentCategory,
            'brand' => trim($this->newEquipmentBrand),
            'status' => $this->newEquipmentStatus,
            'notes' => trim($this->newEquipmentNotes),
        ];

        $this->resetNewEquipmentForm();
        $this->persistEquipment($fields);
    }

    public function addEquipmentPreset(string $presetKey, MissionEnrollmentFieldService $fields): void
    {
        $preset = collect($this->equipmentPresetDefinitions())
            ->firstWhere('key', $presetKey);

        if ($preset === null) {
            return;
        }

        $name = __($preset['name_key']);

        foreach ($this->equipmentItems as $item) {
            if (($item['name'] ?? '') === $name) {
                return;
            }
        }

        $this->equipmentItems[] = [
            'id' => (string) Str::uuid(),
            'name' => $name,
            'category' => $preset['category']->value,
            'brand' => '',
            'status' => EquipmentStatus::Owned->value,
            'notes' => '',
        ];

        $this->persistEquipment($fields);
    }

    public function removeEquipmentItem(string $itemId, MissionEnrollmentFieldService $fields): void
    {
        $this->equipmentItems = array_values(array_filter(
            $this->equipmentItems,
            static fn (array $item): bool => ($item['id'] ?? '') !== $itemId,
        ));

        $this->persistEquipment($fields);
    }

    public function saveEquipment(MissionEnrollmentFieldService $fields): void
    {
        $this->validate([
            'equipmentNotes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->persistEquipment($fields);
    }

    public function openAiWorkoutGenerator(AetherProfileService $profiles, MissionAetherProgramService $aether): void
    {
        if (! $this->userCanAccessAiWorkout()) {
            return;
        }

        $this->openAiQuestionnaire('workout', $profiles, $aether);
    }

    public function openAiMealGenerator(AetherProfileService $profiles, MissionAetherProgramService $aether): void
    {
        if (! $this->userCanAccessAiMeal()) {
            return;
        }

        $this->openAiQuestionnaire('meal', $profiles, $aether);
    }

    public function closeAiQuestionnaire(): void
    {
        $this->showAiQuestionnaire = false;
        $this->aiWizardStep = 1;
        $this->aiIsGenerating = false;
        $this->resetErrorBag();
    }

    public function aiWizardNext(): void
    {
        $this->normalizeAiWizardInputs();
        $this->validate($this->aiWizardRulesForStep($this->aiWizardStep));

        if ($this->aiWizardStep < $this->aiWizardStepCount()) {
            $this->aiWizardStep++;
        }
    }

    public function selectAiAgeRange(string $range): void
    {
        $this->aiAgeRange = $range;
        $this->aiAge = match ($range) {
            '18_29' => 25,
            '30_39' => 35,
            '40_49' => 45,
            '50_plus' => 55,
            default => 28,
        };
    }

    public function aiWizardCanProceed(): bool
    {
        if ($this->aiQuestionnaireTarget === 'meal') {
            return true;
        }

        return match (AetherWorkoutWizardSteps::keyForStep($this->aiWizardStep)) {
            'current_body' => $this->aiCurrentBodyBuild !== '',
            'target_body' => $this->aiTargetBodyGoal !== '',
            'gym_confidence' => $this->aiGymConfidence !== '',
            default => true,
        };
    }

    public function aiWizardStepCount(): int
    {
        return $this->aiQuestionnaireTarget === 'meal'
            ? 4
            : AetherWorkoutWizardSteps::count();
    }

    public function aiWorkoutWizardStepKey(): string
    {
        return AetherWorkoutWizardSteps::keyForStep($this->aiWizardStep);
    }

    public function aiWizardProgressPercent(): float
    {
        $total = max(1, $this->aiWizardStepCount());

        return round(($this->aiWizardStep / $total) * 100, 1);
    }

    public function selectAiTrainingDays(int $days): void
    {
        $this->aiTrainingDaysPerWeek = max(2, min(6, $days));
    }

    public function toggleAiInjury(string $tag): void
    {
        if ($tag === 'none') {
            $this->aiInjuryTags = [];

            return;
        }

        $tags = collect($this->aiInjuryTags);

        if ($tags->contains($tag)) {
            $this->aiInjuryTags = $tags->reject($tag)->values()->all();

            return;
        }

        $this->aiInjuryTags = $tags->push($tag)->values()->all();
    }

    public function aiWizardBack(): void
    {
        if ($this->aiWizardStep > 1) {
            $this->aiWizardStep--;
        }
    }

    public function submitAiQuestionnaire(MissionAetherProgramService $aether): void
    {
        if ($this->aiIsGenerating) {
            return;
        }

        $this->normalizeAiWizardInputs();
        $validationRules = $this->aiQuestionnaireTarget === 'meal'
            ? array_merge(
                $this->aiWizardRulesForStep(1),
                $this->aiWizardRulesForStep(2),
                $this->aiWizardRulesForStep(3),
            )
            : $this->collectWorkoutWizardRules();

        $this->validate($validationRules);

        $this->aiIsGenerating = true;

        try {
            $user = Auth::user();
            $aether->generate(
                $user,
                $this->aiWizardPayload(),
                $this->enrollment,
                $this->aiQuestionnaireTarget,
            );

            $this->enrollment->refresh();
            $this->closeAiQuestionnaire();
            $this->flashSaved();
            session()->flash('mission_ai_status', __('missions.ai_program_applied'));
        } catch (\Throwable $exception) {
            $this->addError('aiWizard', __('missions.ai_generation_failed'));
            report($exception);
        } finally {
            $this->aiIsGenerating = false;
        }
    }

    public function render(
        MissionSupplementLogService $supplements,
        MissionDailyReportService $reports,
        UserAetherProgramHistoryService $programHistory,
        MissionAetherAdherenceService $adherence,
    ): View {
        $locale = app()->getLocale();
        $presenter = new MissionEnrollmentPresenter($this->enrollment);
        $capabilities = $presenter->enabledCapabilities($locale);
        $user = Auth::user();

        $taskConfig = collect($capabilities)->firstWhere('key', 'task')['config'] ?? [];
        $nutritionConfig = collect($capabilities)->firstWhere('key', 'nutrition')['config'] ?? [];

        $snapshot = is_array($this->enrollment->template_snapshot)
            ? $this->enrollment->template_snapshot
            : [];
        $missionIcon = is_string($snapshot['icon'] ?? null) && $snapshot['icon'] !== ''
            ? $snapshot['icon']
            : 'fa-compass';

        $activeWorkoutProgram = $adherence->latestProgramForEnrollment($this->enrollment, 'workout');
        $activeMealProgram = $adherence->latestProgramForEnrollment($this->enrollment, 'meal');

        return view('livewire.missions.workspace', [
            'locale' => $locale,
            'presenter' => $presenter,
            'missionIcon' => $missionIcon,
            'capabilities' => $capabilities,
            'supplementProducts' => $supplements->activeProducts($this->enrollment),
            'supplementIntakes' => $supplements->paginateIntakes($this->enrollment, 10),
            'dailyReports' => $reports->paginateReports($this->enrollment, 5),
            'canAiWorkout' => MissionProGate::canUseFeature($user, $taskConfig, 'ai_workout_plan'),
            'canAiMeal' => MissionProGate::canUseFeature($user, $nutritionConfig, 'ai_meal_plan'),
            'requiresProWorkout' => MissionProGate::featureRequiresPro($taskConfig, 'ai_workout_plan'),
            'requiresProMeal' => MissionProGate::featureRequiresPro($nutritionConfig, 'ai_meal_plan'),
            'dayOptions' => $this->dayOptions($locale),
            'equipmentCategories' => EquipmentCategory::cases(),
            'equipmentStatuses' => EquipmentStatus::cases(),
            'equipmentPresets' => $this->equipmentPresetDefinitions(),
            'aiWizardSteps' => $this->aiWizardStepCount(),
            'aiWizardStepKey' => $this->aiWorkoutWizardStepKey(),
            'aiWizardCanProceed' => $this->aiWizardCanProceed(),
            'aiWizardProgressPercent' => $this->aiWizardProgressPercent(),
            'aiFormOptions' => $this->aiFormOptions(),
            'aiWizardReview' => $this->aiWizardReviewItems(),
            'activeWorkoutProgram' => $activeWorkoutProgram,
            'activeMealProgram' => $activeMealProgram,
            'activeWorkoutProgramSummary' => $activeWorkoutProgram
                ? $programHistory->summaryForProgram($activeWorkoutProgram, $locale)
                : null,
            'activeMealProgramSummary' => $activeMealProgram
                ? $programHistory->summaryForProgram($activeMealProgram, $locale)
                : null,
            'workoutAdherencePercent' => $adherence->workoutAdherencePercent($user, $activeWorkoutProgram),
            'enrollmentProgress' => (float) $this->enrollment->progress_percent,
            'programHistoryUrl' => route('profile').'#my-programs',
        ]);
    }

    private function loadLogsForDate(): void
    {
        $reports = app(MissionDailyReportService::class);
        $adherence = app(MissionAetherAdherenceService::class);
        $user = Auth::user();

        $report = $reports->findForDate($this->enrollment, $this->logDate);

        if ($report !== null) {
            $this->reportWeight = $report->body_weight !== null ? (float) $report->body_weight : null;
            $this->reportMood = $report->mood_score;
            $this->reportEnergy = $report->energy_score;
            $this->reportSleep = $report->sleep_hours !== null ? (float) $report->sleep_hours : null;
            $this->reportTrained = $report->trained_today;
            $this->reportNutritionLogged = $report->nutrition_logged;
            $this->reportHighlights = (string) ($report->highlights ?? '');
            $this->reportChallenges = (string) ($report->challenges ?? '');
            $this->reportNotes = (string) ($report->notes ?? '');

            return;
        }

        $this->reportWeight = null;
        $this->reportMood = null;
        $this->reportEnergy = null;
        $this->reportSleep = null;
        $this->reportTrained = $user ? $adherence->trainedOnDate($user, $this->logDate) : false;
        $this->reportNutritionLogged = false;
        $this->reportHighlights = '';
        $this->reportChallenges = '';
        $this->reportNotes = '';
    }

    private function ensureDefaultSupplements(MissionSupplementLogService $supplements): void
    {
        if ($this->enrollment->supplementProducts()->exists()) {
            return;
        }

        foreach ([
            ['name' => 'Whey Protein', 'brand' => null, 'default_unit' => 'scoop', 'default_amount' => '1'],
            ['name' => 'Creatine', 'brand' => null, 'default_unit' => 'scoop', 'default_amount' => '1'],
        ] as $product) {
            $supplements->addProduct($this->enrollment, $product);
        }
    }

    private function hydrateFromFieldValues(): void
    {
        $values = $this->enrollment->field_values ?? [];

        $this->gymDays = is_array($values['gym_days'] ?? null) ? $values['gym_days'] : [];
        $this->preferredGymTime = (string) ($values['preferred_gym_time'] ?? '18:00');
        $locale = app()->getLocale();
        $this->equipmentNotes = MissionLocalizedText::forLocale($values['equipment_notes'] ?? '', $locale);
        $this->equipmentItems = $this->normalizeEquipmentItems($values['equipment_items'] ?? null);
        $this->registrationProgress = is_array($values['registration_progress'] ?? null)
            ? $values['registration_progress']
            : [];
    }

    private function ensureValidTab(): void
    {
        $allowed = ['program', 'schedule', 'supplements', 'equipment', 'registration', 'daily'];

        if (! in_array($this->activeTab, $allowed, true)) {
            $this->activeTab = 'program';
        }
    }

    private function flashSaved(): void
    {
        $this->dispatch('mission-saved');
    }

    private function userCanAccessAiWorkout(): bool
    {
        $presenter = new MissionEnrollmentPresenter($this->enrollment);
        $taskConfig = collect($presenter->enabledCapabilities(app()->getLocale()))
            ->firstWhere('key', 'task')['config'] ?? [];

        return MissionProGate::canUseFeature(Auth::user(), $taskConfig, 'ai_workout_plan');
    }

    private function userCanAccessAiMeal(): bool
    {
        $presenter = new MissionEnrollmentPresenter($this->enrollment);
        $nutritionConfig = collect($presenter->enabledCapabilities(app()->getLocale()))
            ->firstWhere('key', 'nutrition')['config'] ?? [];

        return MissionProGate::canUseFeature(Auth::user(), $nutritionConfig, 'ai_meal_plan');
    }

    private function openAiQuestionnaire(
        string $target,
        AetherProfileService $profiles,
        MissionAetherProgramService $aether,
    ): void {
        $defaults = $aether->loadWizardDefaults($profiles->forUser(Auth::user()), $this->enrollment);

        $this->aiQuestionnaireTarget = $target;
        $this->aiWizardStep = 1;
        $this->aiAge = (int) $defaults['age'];
        $this->aiGender = (string) $defaults['gender'];
        $this->aiHeightCm = (int) $defaults['height_cm'];
        $this->aiWeightKg = (float) $defaults['weight_kg'];
        $this->aiBodyFatPercent = $defaults['body_fat_percent'];
        $this->aiTrainingExperience = (string) $defaults['training_experience'];
        $this->aiPrimaryGoal = (string) $defaults['primary_goal'];
        $this->aiTrainingDaysPerWeek = (int) $defaults['training_days_per_week'];
        $this->aiSessionDuration = (string) $defaults['session_duration'];
        $this->aiPreferredWorkoutTime = (string) $defaults['preferred_workout_time'];
        $this->aiEquipment = (string) $defaults['equipment'];
        $this->aiDietaryPattern = (string) $defaults['dietary_pattern'];
        $this->aiCookingAbility = (string) $defaults['cooking_ability'];
        $this->aiCoachingTone = (string) $defaults['coaching_tone'];
        $this->aiMotivationStyle = (string) $defaults['motivation_style'];
        $this->aiTrainingStyle = (string) $defaults['training_style'];
        $this->aiInjuryTags = is_array($defaults['injury_tags'] ?? null) ? $defaults['injury_tags'] : [];
        $this->aiAgeRange = (string) ($defaults['age_range'] ?? '18_29');
        $this->selectAiAgeRange($this->aiAgeRange);
        $this->aiCurrentBodyBuild = (string) ($defaults['current_body_build'] ?? '');
        $this->aiTargetBodyGoal = (string) ($defaults['target_body_goal'] ?? '');
        $this->aiGymConfidence = (string) ($defaults['gym_confidence'] ?? '');
        $this->showAiQuestionnaire = true;
        $this->resetErrorBag();
    }

    /**
     * @return array<string, mixed>
     */
    private function aiWizardPayload(): array
    {
        return [
            'age' => $this->aiAge,
            'gender' => $this->aiGender,
            'height_cm' => $this->aiHeightCm,
            'weight_kg' => $this->aiWeightKg,
            'body_fat_percent' => $this->aiBodyFatPercent,
            'primary_goal' => $this->aiPrimaryGoal,
            'training_days_per_week' => $this->aiTrainingDaysPerWeek,
            'session_duration' => $this->aiSessionDuration,
            'preferred_workout_time' => $this->aiPreferredWorkoutTime,
            'equipment' => $this->aiEquipment,
            'injury_tags' => $this->aiInjuryTags,
            'dietary_pattern' => $this->aiDietaryPattern,
            'cooking_ability' => $this->aiCookingAbility,
            'coaching_tone' => $this->aiCoachingTone,
            'motivation_style' => $this->aiMotivationStyle,
            'training_style' => $this->aiTrainingStyle,
            'training_experience' => $this->deriveTrainingExperience(),
            'current_body_build' => $this->aiCurrentBodyBuild,
            'target_body_goal' => $this->aiTargetBodyGoal,
            'gym_confidence' => $this->aiGymConfidence,
            'age_range' => $this->aiAgeRange,
        ];
    }

    private function deriveTrainingExperience(): string
    {
        if ($this->aiGymConfidence !== '') {
            return match ($this->aiGymConfidence) {
                GymConfidence::NeverBeen->value, GymConfidence::LostUnsure->value => TrainingExperience::Beginner->value,
                GymConfidence::BasicsUnsure->value => TrainingExperience::Beginner->value,
                GymConfidence::ComfortableGuidance->value => TrainingExperience::Intermediate->value,
                GymConfidence::ConfidentPlan->value => TrainingExperience::Advanced->value,
                default => TrainingExperience::Intermediate->value,
            };
        }

        return match ($this->aiTrainingStyle) {
            TrainingStylePreference::HeavyWeights->value, TrainingStylePreference::Hiit->value => TrainingExperience::Intermediate->value,
            default => TrainingExperience::Beginner->value,
        };
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    private function aiWizardReviewItems(): array
    {
        $locale = app()->getLocale();
        $options = $this->aiFormOptions();

        $label = static function (array $items, string $value): string {
            foreach ($items as $item) {
                if ($item['value'] === $value) {
                    return $item['label'];
                }
            }

            return $value;
        };

        $injuryLabels = collect($this->aiInjuryTags)
            ->map(fn (string $tag): string => $label($options['injuries'], $tag))
            ->implode(' · ');

        if ($this->aiQuestionnaireTarget === 'meal') {
            return [
                ['label' => __('missions.ai_review_days', locale: $locale), 'value' => __('missions.ai_review_days_value', ['days' => eg_num($this->aiTrainingDaysPerWeek)], locale: $locale)],
                ['label' => __('missions.ai_review_session', locale: $locale), 'value' => $label($options['session_durations'], $this->aiSessionDuration)],
                ['label' => __('missions.ai_review_injuries', locale: $locale), 'value' => $injuryLabels !== '' ? $injuryLabels : __('missions.ai_injury_none', locale: $locale)],
                ['label' => __('missions.ai_review_goal', locale: $locale), 'value' => $label($options['goals'], $this->aiPrimaryGoal)],
                ['label' => __('missions.ai_review_equipment', locale: $locale), 'value' => $label($options['equipment'], $this->aiEquipment)],
                ['label' => __('missions.ai_review_diet', locale: $locale), 'value' => $label($options['dietary'], $this->aiDietaryPattern)],
                ['label' => __('missions.ai_review_style', locale: $locale), 'value' => $label($options['training_styles'], $this->aiTrainingStyle)],
                ['label' => __('missions.ai_review_motivation', locale: $locale), 'value' => $label($options['motivation'], $this->aiMotivationStyle)],
            ];
        }

        return [
            ['label' => __('missions.ai_review_gender', locale: $locale), 'value' => $label($options['genders'], $this->aiGender)],
            ['label' => __('missions.ai_review_age', locale: $locale), 'value' => $label($options['age_ranges'], $this->aiAgeRange)],
            ['label' => __('missions.ai_review_height', locale: $locale), 'value' => __('missions.ai_review_height_value', ['height' => eg_num($this->aiHeightCm)], locale: $locale)],
            ['label' => __('missions.ai_review_weight', locale: $locale), 'value' => __('missions.ai_review_weight_value', ['weight' => eg_num($this->aiWeightKg)], locale: $locale)],
            ['label' => __('missions.ai_review_current_body', locale: $locale), 'value' => $label($options['body_builds'], $this->aiCurrentBodyBuild)],
            ['label' => __('missions.ai_review_target_body', locale: $locale), 'value' => $label($options['body_goals'], $this->aiTargetBodyGoal)],
            ['label' => __('missions.ai_review_goal', locale: $locale), 'value' => $label($options['goals'], $this->aiPrimaryGoal)],
            ['label' => __('missions.ai_review_gym_confidence', locale: $locale), 'value' => $label($options['gym_confidence'], $this->aiGymConfidence)],
            ['label' => __('missions.ai_review_days', locale: $locale), 'value' => __('missions.ai_review_days_value', ['days' => eg_num($this->aiTrainingDaysPerWeek)], locale: $locale)],
            ['label' => __('missions.ai_review_session', locale: $locale), 'value' => $label($options['session_durations'], $this->aiSessionDuration)],
            ['label' => __('missions.ai_review_equipment', locale: $locale), 'value' => $label($options['equipment'], $this->aiEquipment)],
            ['label' => __('missions.ai_review_injuries', locale: $locale), 'value' => $injuryLabels !== '' ? $injuryLabels : __('missions.ai_injury_none', locale: $locale)],
            ['label' => __('missions.ai_review_style', locale: $locale), 'value' => $label($options['training_styles'], $this->aiTrainingStyle)],
            ['label' => __('missions.ai_review_motivation', locale: $locale), 'value' => $label($options['motivation'], $this->aiMotivationStyle)],
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function collectWorkoutWizardRules(): array
    {
        $rules = [];

        for ($step = 1; $step < AetherWorkoutWizardSteps::count(); $step++) {
            $rules = array_merge($rules, $this->aiWizardRulesForStep($step));
        }

        return $rules;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function aiWizardRulesForStep(int $step): array
    {
        if ($this->aiQuestionnaireTarget === 'meal') {
            return match ($step) {
                1 => [
                    'aiTrainingDaysPerWeek' => ['required', 'integer', 'min:2', 'max:6'],
                    'aiSessionDuration' => $this->enumValueRule(SessionDuration::class),
                    'aiInjuryTags' => ['nullable', 'array'],
                    'aiInjuryTags.*' => ['string', Rule::in(['knee', 'lower_back', 'shoulder', 'wrist'])],
                ],
                2 => [
                    'aiPrimaryGoal' => $this->enumValueRule(PrimaryGoal::class),
                    'aiEquipment' => $this->enumValueRule(EquipmentAccess::class),
                    'aiDietaryPattern' => $this->enumValueRule(DietaryPattern::class),
                ],
                3 => [
                    'aiTrainingStyle' => $this->enumValueRule(TrainingStylePreference::class),
                    'aiMotivationStyle' => $this->enumValueRule(MotivationStyle::class),
                ],
                default => [],
            };
        }

        return match (AetherWorkoutWizardSteps::keyForStep($step)) {
            'gender' => [
                'aiGender' => $this->enumValueRule(Gender::class),
            ],
            'age' => [
                'aiAgeRange' => ['required', 'string', Rule::in(['18_29', '30_39', '40_49', '50_plus'])],
            ],
            'height' => [
                'aiHeightCm' => ['required', 'integer', 'min:120', 'max:230'],
            ],
            'weight' => [
                'aiWeightKg' => ['required', 'numeric', 'min:35', 'max:200'],
            ],
            'current_body' => [
                'aiCurrentBodyBuild' => $this->enumValueRule(BodyBuild::class),
            ],
            'target_body' => [
                'aiTargetBodyGoal' => $this->enumValueRule(BodyGoal::class),
            ],
            'goal' => [
                'aiPrimaryGoal' => $this->enumValueRule(PrimaryGoal::class),
            ],
            'gym_confidence' => [
                'aiGymConfidence' => $this->enumValueRule(GymConfidence::class),
            ],
            'days' => [
                'aiTrainingDaysPerWeek' => ['required', 'integer', 'min:2', 'max:6'],
            ],
            'session' => [
                'aiSessionDuration' => $this->enumValueRule(SessionDuration::class),
            ],
            'equipment' => [
                'aiEquipment' => $this->enumValueRule(EquipmentAccess::class),
            ],
            'injuries' => [
                'aiInjuryTags' => ['nullable', 'array'],
                'aiInjuryTags.*' => ['string', Rule::in(['knee', 'lower_back', 'shoulder', 'wrist'])],
            ],
            'style' => [
                'aiTrainingStyle' => $this->enumValueRule(TrainingStylePreference::class),
            ],
            'motivation' => [
                'aiMotivationStyle' => $this->enumValueRule(MotivationStyle::class),
            ],
            default => [],
        };
    }

    private function normalizeAiWizardInputs(): void
    {
        $this->aiGender = $this->normalizeBackedEnum($this->aiGender, Gender::class);
        $this->aiCurrentBodyBuild = $this->normalizeBackedEnum($this->aiCurrentBodyBuild, BodyBuild::class);
        $this->aiTargetBodyGoal = $this->normalizeBackedEnum($this->aiTargetBodyGoal, BodyGoal::class);
        $this->aiGymConfidence = $this->normalizeBackedEnum($this->aiGymConfidence, GymConfidence::class);
        $this->aiTrainingExperience = $this->deriveTrainingExperience();
        $this->aiPrimaryGoal = $this->normalizeBackedEnum($this->aiPrimaryGoal, PrimaryGoal::class);
        $this->aiTrainingStyle = $this->normalizeBackedEnum($this->aiTrainingStyle, TrainingStylePreference::class);
        $this->aiSessionDuration = $this->normalizeBackedEnum($this->aiSessionDuration, SessionDuration::class);
        $this->aiPreferredWorkoutTime = $this->normalizeBackedEnum($this->aiPreferredWorkoutTime, WorkoutTimePreference::class);
        $this->aiEquipment = $this->normalizeBackedEnum($this->aiEquipment, EquipmentAccess::class);
        $this->aiDietaryPattern = $this->normalizeBackedEnum($this->aiDietaryPattern, DietaryPattern::class);
        $this->aiCookingAbility = $this->normalizeBackedEnum($this->aiCookingAbility, CookingAbility::class);
        $this->aiCoachingTone = $this->normalizeBackedEnum($this->aiCoachingTone, CoachingTone::class);
        $this->aiMotivationStyle = $this->normalizeBackedEnum($this->aiMotivationStyle, MotivationStyle::class);
    }

    /**
     * @param  class-string<\BackedEnum>  $enumClass
     */
    private function normalizeBackedEnum(string $value, string $enumClass): string
    {
        $normalized = strtolower(trim($value));

        foreach ($enumClass::cases() as $case) {
            if ($case->value === $normalized || strtolower($case->name) === $normalized) {
                return $case->value;
            }
        }

        return $normalized;
    }

    /**
     * @param  class-string<\BackedEnum>  $enumClass
     * @return array<int, mixed>
     */
    private function enumValueRule(string $enumClass): array
    {
        return [
            'required',
            'string',
            Rule::in(array_map(static fn (\BackedEnum $case): string => $case->value, $enumClass::cases())),
        ];
    }

    /**
     * @return array<string, list<array{value: string, label: string}>>
     */
    private function aiFormOptions(): array
    {
        $locale = app()->getLocale();

        return [
            'genders' => $this->enumOptions(Gender::cases(), 'missions.ai_gender_', $locale),
            'age_ranges' => [
                ['value' => '18_29', 'label' => __('missions.ai_age_18_29', locale: $locale)],
                ['value' => '30_39', 'label' => __('missions.ai_age_30_39', locale: $locale)],
                ['value' => '40_49', 'label' => __('missions.ai_age_40_49', locale: $locale)],
                ['value' => '50_plus', 'label' => __('missions.ai_age_50_plus', locale: $locale)],
            ],
            'body_builds' => $this->enumOptions(BodyBuild::cases(), 'missions.ai_body_build_', $locale),
            'body_goals' => $this->enumOptions(BodyGoal::cases(), 'missions.ai_body_goal_', $locale),
            'gym_confidence' => $this->enumOptions(GymConfidence::cases(), 'missions.ai_gym_confidence_', $locale),
            'goals' => $this->enumOptions(PrimaryGoal::cases(), 'missions.ai_goal_', $locale),
            'goal_icons' => [
                'fat_loss' => 'fa-fire',
                'muscle_gain' => 'fa-dumbbell',
                'recomposition' => 'fa-scale-balanced',
                'strength' => 'fa-weight-hanging',
                'endurance' => 'fa-person-running',
                'aesthetics' => 'fa-sparkles',
                'health' => 'fa-heart-pulse',
            ],
            'experience' => $this->enumOptions(TrainingExperience::cases(), 'missions.ai_experience_', $locale),
            'experience_icons' => [
                'beginner' => 'fa-seedling',
                'intermediate' => 'fa-chart-line',
                'advanced' => 'fa-bolt',
                'elite' => 'fa-crown',
            ],
            'session_durations' => $this->enumOptions(SessionDuration::cases(), 'missions.ai_session_', $locale),
            'workout_times' => $this->enumOptions(WorkoutTimePreference::cases(), 'missions.ai_time_', $locale),
            'equipment' => $this->enumOptions(EquipmentAccess::cases(), 'missions.ai_equipment_', $locale),
            'dietary' => $this->enumOptions(DietaryPattern::cases(), 'missions.ai_diet_', $locale),
            'cooking' => $this->enumOptions(CookingAbility::cases(), 'missions.ai_cooking_', $locale),
            'tones' => $this->enumOptions(CoachingTone::cases(), 'missions.ai_tone_', $locale),
            'motivation' => $this->enumOptions(MotivationStyle::cases(), 'missions.ai_motivation_', $locale),
            'training_styles' => $this->enumOptions(TrainingStylePreference::cases(), 'missions.ai_training_style_', $locale),
            'training_style_icons' => [
                'heavy_weights' => 'fa-dumbbell',
                'hiit' => 'fa-bolt',
                'yoga_stretch' => 'fa-spa',
                'cardio' => 'fa-person-running',
            ],
            'injuries' => [
                ['value' => 'knee', 'label' => __('missions.ai_injury_knee', locale: $locale), 'icon' => 'fa-person-cane'],
                ['value' => 'lower_back', 'label' => __('missions.ai_injury_back', locale: $locale), 'icon' => 'fa-chair'],
                ['value' => 'shoulder', 'label' => __('missions.ai_injury_shoulder', locale: $locale), 'icon' => 'fa-hand'],
                ['value' => 'none', 'label' => __('missions.ai_injury_none', locale: $locale), 'icon' => 'fa-circle-check'],
            ],
            'day_options' => collect(range(2, 6))
                ->map(fn (int $day): array => [
                    'value' => $day,
                    'label' => __('missions.ai_days_option', ['days' => eg_num($day)], locale: $locale),
                ])
                ->all(),
        ];
    }

    /**
     * @param  array<int, \BackedEnum>  $cases
     * @return list<array{value: string, label: string}>
     */
    private function enumOptions(array $cases, string $labelPrefix, string $locale): array
    {
        return collect($cases)
            ->map(fn (\BackedEnum $case): array => [
                'value' => $case->value,
                'label' => __($labelPrefix.$case->value, locale: $locale),
            ])
            ->all();
    }

    private function persistEquipment(MissionEnrollmentFieldService $fields): void
    {
        $locale = app()->getLocale();

        $fields->merge($this->enrollment, [
            'equipment_items' => $this->equipmentItems,
            'equipment_notes' => MissionLocalizedText::merge(
                $this->enrollment->field_values['equipment_notes'] ?? '',
                $this->equipmentNotes,
                $locale,
            ),
        ], Auth::user());

        $this->flashSaved();
    }

    private function resetNewEquipmentForm(): void
    {
        $this->newEquipmentName = '';
        $this->newEquipmentCategory = EquipmentCategory::Accessories->value;
        $this->newEquipmentBrand = '';
        $this->newEquipmentStatus = EquipmentStatus::Owned->value;
        $this->newEquipmentNotes = '';
    }

    /**
     * @return list<array{key: string, category: EquipmentCategory, name_key: string}>
     */
    private function equipmentPresetDefinitions(): array
    {
        return [
            ['key' => 'belt', 'category' => EquipmentCategory::Belt, 'name_key' => 'missions.equipment_preset_belt'],
            ['key' => 'shoes', 'category' => EquipmentCategory::Shoes, 'name_key' => 'missions.equipment_preset_shoes'],
            ['key' => 'straps', 'category' => EquipmentCategory::Straps, 'name_key' => 'missions.equipment_preset_straps'],
            ['key' => 'sleeves', 'category' => EquipmentCategory::Sleeves, 'name_key' => 'missions.equipment_preset_sleeves'],
            ['key' => 'shaker', 'category' => EquipmentCategory::Accessories, 'name_key' => 'missions.equipment_preset_shaker'],
            ['key' => 'gloves', 'category' => EquipmentCategory::Apparel, 'name_key' => 'missions.equipment_preset_gloves'],
        ];
    }

    /**
     * @return list<array{id: string, name: string, category: string, brand: string, status: string, notes: string}>
     */
    private function normalizeEquipmentItems(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $items = [];

        foreach ($raw as $item) {
            if (! is_array($item) || ! filled($item['name'] ?? null)) {
                continue;
            }

            $category = EquipmentCategory::tryFrom((string) ($item['category'] ?? ''))
                ?? EquipmentCategory::Other;
            $status = EquipmentStatus::tryFrom((string) ($item['status'] ?? ''))
                ?? EquipmentStatus::Owned;

            $items[] = [
                'id' => (string) ($item['id'] ?? Str::uuid()),
                'name' => (string) $item['name'],
                'category' => $category->value,
                'brand' => (string) ($item['brand'] ?? ''),
                'status' => $status->value,
                'notes' => (string) ($item['notes'] ?? ''),
            ];
        }

        return $items;
    }

    private function dayOptions(string $locale): array
    {
        $map = [
            'sat' => ['en' => 'Saturday', 'fa' => 'شنبه'],
            'sun' => ['en' => 'Sunday', 'fa' => 'یکشنبه'],
            'mon' => ['en' => 'Monday', 'fa' => 'دوشنبه'],
            'tue' => ['en' => 'Tuesday', 'fa' => 'سه‌شنبه'],
            'wed' => ['en' => 'Wednesday', 'fa' => 'چهارشنبه'],
            'thu' => ['en' => 'Thursday', 'fa' => 'پنجشنبه'],
            'fri' => ['en' => 'Friday', 'fa' => 'جمعه'],
        ];

        return collect($map)
            ->map(fn (array $labels, string $value): array => [
                'value' => $value,
                'label' => $labels[$locale] ?? $labels['en'],
            ])
            ->values()
            ->all();
    }
}
