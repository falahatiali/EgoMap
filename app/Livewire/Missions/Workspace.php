<?php

namespace App\Livewire\Missions;

use App\Services\Missions\MissionAetherProgramService;
use App\Services\Profile\UserAetherProgramHistoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\AetherEngine\Enums\CoachingTone;
use Modules\AetherEngine\Enums\CookingAbility;
use Modules\AetherEngine\Enums\DietaryPattern;
use Modules\AetherEngine\Enums\EquipmentAccess;
use Modules\AetherEngine\Enums\Gender;
use Modules\AetherEngine\Enums\MotivationStyle;
use Modules\AetherEngine\Enums\PrimaryGoal;
use Modules\AetherEngine\Enums\SessionDuration;
use Modules\AetherEngine\Enums\TrainingExperience;
use Modules\AetherEngine\Enums\WorkoutTimePreference;
use Modules\AetherEngine\Services\AetherProfileService;
use Modules\MissionEngine\Enums\EquipmentCategory;
use Modules\MissionEngine\Enums\EquipmentStatus;
use Modules\MissionEngine\Enums\MealType;
use Modules\MissionEngine\Models\MissionEnrollment;
use Modules\MissionEngine\Models\MissionWorkoutSession;
use Modules\MissionEngine\Services\MissionDailyReportService;
use Modules\MissionEngine\Services\MissionEnrollmentFieldService;
use Modules\MissionEngine\Services\MissionNutritionLogService;
use Modules\MissionEngine\Services\MissionSupplementLogService;
use Modules\MissionEngine\Services\MissionWorkoutLogService;
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
    public string $activeTab = 'workout';

    public string $logDate;

    /** Schedule */
    /** @var list<string> */
    public array $gymDays = [];

    public string $preferredGymTime = '18:00';

    /** @var list<array{day: string, focus: string, notes: string}> */
    public array $workoutPlan = [];

    public string $mealPlanNotes = '';

    /** Workout session log */
    public string $workoutDayKey = 'sat';

    public string $workoutFocus = '';

    public ?int $workoutDuration = null;

    public string $workoutSessionNotes = '';

    /**
     * @var list<array{name: string, notes: string, sets: list<array{reps: string, weight: string, notes: string}>}>
     */
    public array $workoutExercises = [];

    /** Nutrition log */
    public string $nutritionDayNotes = '';

    public ?int $nutritionQuality = null;

    /**
     * @var list<array{meal_type: string, meal_time: string, notes: string, items: list<array{name: string, quantity: string, unit: string, calories: string, protein_g: string}>}>
     */
    public array $nutritionMeals = [];

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

    public string $aiInjuriesLimitations = '';

    public string $aiDietaryPattern = 'omnivore';

    public string $aiCookingAbility = 'simple';

    public string $aiAllergiesText = '';

    public string $aiCoachingTone = 'technical';

    public string $aiMotivationStyle = 'feeling_strong';

    public string $aiFavoriteExercisesText = '';

    public string $aiDislikedExercisesText = '';

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
        $this->resetWorkoutForm();
        $this->resetNutritionForm();
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

    public function saveWorkoutPlan(MissionEnrollmentFieldService $fields): void
    {
        $fields->merge($this->enrollment, [
            'workout_plan' => $this->persistWorkoutPlanRows(),
        ], Auth::user());

        $this->flashSaved();
    }

    public function addWorkoutPlanRow(): void
    {
        $this->workoutPlan[] = ['day' => 'sat', 'focus' => '', 'notes' => ''];
    }

    public function removeWorkoutPlanRow(int $index): void
    {
        unset($this->workoutPlan[$index]);
        $this->workoutPlan = array_values($this->workoutPlan);
    }

    public function addWorkoutExercise(): void
    {
        $this->workoutExercises[] = [
            'name' => '',
            'notes' => '',
            'sets' => [
                ['reps' => '', 'weight' => '', 'notes' => ''],
                ['reps' => '', 'weight' => '', 'notes' => ''],
                ['reps' => '', 'weight' => '', 'notes' => ''],
            ],
        ];
    }

    public function removeWorkoutExercise(int $index): void
    {
        unset($this->workoutExercises[$index]);
        $this->workoutExercises = array_values($this->workoutExercises);
    }

    public function addWorkoutSet(int $exerciseIndex): void
    {
        if (! isset($this->workoutExercises[$exerciseIndex])) {
            return;
        }

        $this->workoutExercises[$exerciseIndex]['sets'][] = ['reps' => '', 'weight' => '', 'notes' => ''];
    }

    public function removeWorkoutSet(int $exerciseIndex, int $setIndex): void
    {
        if (! isset($this->workoutExercises[$exerciseIndex]['sets'][$setIndex])) {
            return;
        }

        unset($this->workoutExercises[$exerciseIndex]['sets'][$setIndex]);
        $this->workoutExercises[$exerciseIndex]['sets'] = array_values($this->workoutExercises[$exerciseIndex]['sets']);
    }

    public function saveWorkoutSession(MissionWorkoutLogService $workouts): void
    {
        $this->validate([
            'logDate' => ['required', 'date'],
            'workoutFocus' => ['nullable', 'string', 'max:120'],
            'workoutDuration' => ['nullable', 'integer', 'min:1', 'max:600'],
            'workoutExercises' => ['required', 'array', 'min:1'],
            'workoutExercises.*.name' => ['required', 'string', 'max:120'],
        ]);

        $exercises = array_values(array_filter(
            $this->normalizeWorkoutExercises(),
            static fn (array $exercise): bool => $exercise['sets'] !== [],
        ));

        if ($exercises === []) {
            $this->addError('workoutExercises', __('missions.workout_need_exercise'));

            return;
        }

        $session = $workouts->saveSession($this->enrollment, Auth::user(), [
            'session_date' => $this->logDate,
            'day_key' => $this->workoutDayKey,
            'focus' => $this->workoutFocus ?: null,
            'duration_minutes' => $this->workoutDuration,
            'notes' => $this->workoutSessionNotes ?: null,
            'exercises' => $exercises,
        ]);

        $this->reportTrained = true;
        $this->loadWorkoutIntoForm($session);
        $this->flashSaved();
    }

    public function addNutritionMeal(): void
    {
        $this->nutritionMeals[] = [
            'meal_type' => MealType::Breakfast->value,
            'meal_time' => '',
            'notes' => '',
            'items' => [
                ['name' => '', 'quantity' => '', 'unit' => '', 'calories' => '', 'protein_g' => ''],
            ],
        ];
    }

    public function removeNutritionMeal(int $index): void
    {
        unset($this->nutritionMeals[$index]);
        $this->nutritionMeals = array_values($this->nutritionMeals);
    }

    public function addMealItem(int $mealIndex): void
    {
        if (! isset($this->nutritionMeals[$mealIndex])) {
            return;
        }

        $this->nutritionMeals[$mealIndex]['items'][] = [
            'name' => '', 'quantity' => '', 'unit' => '', 'calories' => '', 'protein_g' => '',
        ];
    }

    public function removeMealItem(int $mealIndex, int $itemIndex): void
    {
        if (! isset($this->nutritionMeals[$mealIndex]['items'][$itemIndex])) {
            return;
        }

        unset($this->nutritionMeals[$mealIndex]['items'][$itemIndex]);
        $this->nutritionMeals[$mealIndex]['items'] = array_values($this->nutritionMeals[$mealIndex]['items']);
    }

    public function saveNutritionDay(MissionNutritionLogService $nutrition): void
    {
        $this->validate([
            'logDate' => ['required', 'date'],
            'nutritionMeals' => ['required', 'array', 'min:1'],
        ]);

        $meals = $this->normalizeNutritionMeals();

        if ($meals === []) {
            $this->addError('nutritionMeals', __('missions.nutrition_need_meal'));

            return;
        }

        $nutrition->saveDay($this->enrollment, Auth::user(), [
            'log_date' => $this->logDate,
            'day_notes' => $this->nutritionDayNotes ?: null,
            'meal_quality_score' => $this->nutritionQuality,
            'meals' => $meals,
        ]);

        $this->reportNutritionLogged = true;
        $this->loadLogsForDate();
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
        MissionWorkoutLogService $workouts,
        MissionNutritionLogService $nutrition,
    ): void {
        $this->validate([
            'logDate' => ['required', 'date'],
            'reportWeight' => ['nullable', 'numeric', 'min:20', 'max:400'],
            'reportMood' => ['nullable', 'integer', 'min:1', 'max:10'],
            'reportEnergy' => ['nullable', 'integer', 'min:1', 'max:10'],
            'reportSleep' => ['nullable', 'numeric', 'min:0', 'max:24'],
        ]);

        $workoutSession = $workouts->findSessionForDate($this->enrollment, $this->logDate);
        $nutritionDay = $nutrition->findDayForDate($this->enrollment, $this->logDate);

        $reports->save($this->enrollment, Auth::user(), [
            'report_date' => $this->logDate,
            'body_weight' => $this->reportWeight,
            'mood_score' => $this->reportMood,
            'energy_score' => $this->reportEnergy,
            'sleep_hours' => $this->reportSleep,
            'trained_today' => $this->reportTrained || $workoutSession !== null,
            'nutrition_logged' => $this->reportNutritionLogged || $nutritionDay !== null,
            'highlights' => $this->reportHighlights ?: null,
            'challenges' => $this->reportChallenges ?: null,
            'notes' => $this->reportNotes ?: null,
            'workout_session_id' => $workoutSession?->id,
            'nutrition_day_id' => $nutritionDay?->id,
        ]);

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

        if ($this->aiWizardStep < 3) {
            $this->aiWizardStep++;
        }
    }

    public function aiWizardBack(): void
    {
        if ($this->aiWizardStep > 1) {
            $this->aiWizardStep--;
        }
    }

    public function submitAiQuestionnaire(
        MissionAetherProgramService $aether,
        MissionEnrollmentFieldService $fields,
    ): void {
        if ($this->aiIsGenerating) {
            return;
        }

        $this->normalizeAiWizardInputs();
        $this->validate(array_merge(
            $this->aiWizardRulesForStep(1),
            $this->aiWizardRulesForStep(2),
            $this->aiWizardRulesForStep(3),
        ));

        $this->aiIsGenerating = true;

        try {
            $user = Auth::user();
            $locale = app()->getLocale();
            $program = $aether->generate($user, $this->aiWizardPayload());
            $program->update([
                'applied_target' => $this->aiQuestionnaireTarget,
                'mission_enrollment_id' => $this->enrollment->id,
            ]);

            if (in_array($this->aiQuestionnaireTarget, ['workout', 'meal'], true)) {
                $merge = [];

                if ($this->aiQuestionnaireTarget === 'workout') {
                    $rows = $aether->workoutPlanRowsForLocale($program, $locale);
                    $this->workoutPlan = $rows;
                    $merge['workout_plan'] = $aether->persistWorkoutPlanRows($this->enrollment, $rows, $locale);
                }

                if ($this->aiQuestionnaireTarget === 'meal') {
                    $mealNotes = $aether->mealPlanNotesForLocale($program, $locale);
                    $merge['meal_plan_notes'] = MissionLocalizedText::merge(
                        $this->enrollment->field_values['meal_plan_notes'] ?? '',
                        $mealNotes,
                        $locale,
                    );
                }

                if ($merge !== []) {
                    $fields->merge($this->enrollment, $merge, $user);
                    $this->enrollment->refresh();
                    $this->hydrateFromFieldValues();
                }
            }

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
        MissionWorkoutLogService $workouts,
        MissionNutritionLogService $nutrition,
        MissionSupplementLogService $supplements,
        MissionDailyReportService $reports,
        UserAetherProgramHistoryService $programHistory,
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

        return view('livewire.missions.workspace', [
            'locale' => $locale,
            'presenter' => $presenter,
            'missionIcon' => $missionIcon,
            'capabilities' => $capabilities,
            'workoutHistory' => $workouts->paginateSessions($this->enrollment, 5),
            'nutritionHistory' => $nutrition->paginateDays($this->enrollment, 5),
            'supplementProducts' => $supplements->activeProducts($this->enrollment),
            'supplementIntakes' => $supplements->paginateIntakes($this->enrollment, 10),
            'dailyReports' => $reports->paginateReports($this->enrollment, 5),
            'mealTypes' => MealType::cases(),
            'canAiWorkout' => MissionProGate::canUseFeature($user, $taskConfig, 'ai_workout_plan'),
            'canAiMeal' => MissionProGate::canUseFeature($user, $nutritionConfig, 'ai_meal_plan'),
            'requiresProWorkout' => MissionProGate::featureRequiresPro($taskConfig, 'ai_workout_plan'),
            'requiresProMeal' => MissionProGate::featureRequiresPro($nutritionConfig, 'ai_meal_plan'),
            'dayOptions' => $this->dayOptions($locale),
            'equipmentCategories' => EquipmentCategory::cases(),
            'equipmentStatuses' => EquipmentStatus::cases(),
            'equipmentPresets' => $this->equipmentPresetDefinitions(),
            'aiWizardSteps' => 3,
            'aiFormOptions' => $this->aiFormOptions(),
            'hasUserWorkoutProgram' => $programHistory->hasAppliedTarget($user, 'workout'),
            'hasUserMealProgram' => $programHistory->hasAppliedTarget($user, 'meal'),
            'activeWorkoutProgram' => $activeWorkoutProgram = $programHistory->latestForTarget($user, 'workout'),
            'activeMealProgram' => $activeMealProgram = $programHistory->latestForTarget($user, 'meal'),
            'activeWorkoutProgramSummary' => $activeWorkoutProgram
                ? $programHistory->summaryForProgram($activeWorkoutProgram, $locale)
                : null,
            'activeMealProgramSummary' => $activeMealProgram
                ? $programHistory->summaryForProgram($activeMealProgram, $locale)
                : null,
            'programHistoryUrl' => route('profile').'#my-programs',
        ]);
    }

    private function loadLogsForDate(): void
    {
        $workouts = app(MissionWorkoutLogService::class);
        $nutrition = app(MissionNutritionLogService::class);
        $reports = app(MissionDailyReportService::class);

        $session = $workouts->findSessionForDate($this->enrollment, $this->logDate);

        if ($session !== null) {
            $this->loadWorkoutIntoForm($session);
        } else {
            $this->resetWorkoutForm();
        }

        $day = $nutrition->findDayForDate($this->enrollment, $this->logDate);

        if ($day !== null) {
            $this->nutritionDayNotes = (string) ($day->day_notes ?? '');
            $this->nutritionQuality = $day->meal_quality_score;
            $this->nutritionMeals = $day->meals->map(fn ($meal): array => [
                'meal_type' => $meal->meal_type->value,
                'meal_time' => $meal->meal_time ? substr((string) $meal->meal_time, 0, 5) : '',
                'notes' => (string) ($meal->notes ?? ''),
                'items' => $meal->items->map(fn ($item): array => [
                    'name' => $item->name,
                    'quantity' => $item->quantity !== null ? (string) $item->quantity : '',
                    'unit' => (string) ($item->unit ?? ''),
                    'calories' => $item->calories !== null ? (string) $item->calories : '',
                    'protein_g' => $item->protein_g !== null ? (string) $item->protein_g : '',
                ])->all(),
            ])->all();
        } else {
            $this->resetNutritionForm();
        }

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
        }
    }

    private function loadWorkoutIntoForm(MissionWorkoutSession $session): void
    {
        $this->workoutDayKey = (string) ($session->day_key ?? 'sat');
        $this->workoutFocus = MissionLocalizedText::forLocale($session->focus ?? '', app()->getLocale());
        $this->workoutDuration = $session->duration_minutes;
        $this->workoutSessionNotes = (string) ($session->notes ?? '');
        $this->workoutExercises = $session->exercises->map(fn ($exercise): array => [
            'name' => $exercise->name,
            'notes' => (string) ($exercise->notes ?? ''),
            'sets' => $exercise->sets->map(fn ($set): array => [
                'reps' => $set->reps !== null ? (string) $set->reps : '',
                'weight' => $set->weight !== null ? (string) $set->weight : '',
                'notes' => (string) ($set->notes ?? ''),
            ])->all(),
        ])->all();
    }

    private function resetWorkoutForm(): void
    {
        $this->workoutDayKey = $this->dayKeyFromDate($this->logDate);
        $this->workoutFocus = '';
        $this->workoutDuration = null;
        $this->workoutSessionNotes = '';
        $this->workoutExercises = [
            [
                'name' => '',
                'notes' => '',
                'sets' => [
                    ['reps' => '', 'weight' => '', 'notes' => ''],
                    ['reps' => '', 'weight' => '', 'notes' => ''],
                    ['reps' => '', 'weight' => '', 'notes' => ''],
                ],
            ],
        ];
    }

    private function resetNutritionForm(): void
    {
        $this->nutritionDayNotes = '';
        $this->nutritionQuality = null;
        $this->nutritionMeals = [
            [
                'meal_type' => MealType::Breakfast->value,
                'meal_time' => '08:00',
                'notes' => '',
                'items' => [
                    ['name' => '', 'quantity' => '', 'unit' => '', 'calories' => '', 'protein_g' => ''],
                ],
            ],
        ];
    }

    /**
     * @return list<array{name: string, notes: string|null, sets: list<array{reps: int|null, weight: float|null, weight_unit: string, notes: string|null}>}>
     */
    private function normalizeWorkoutExercises(): array
    {
        $normalized = [];

        foreach ($this->workoutExercises as $exercise) {
            $name = trim((string) ($exercise['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $sets = [];

            foreach ($exercise['sets'] ?? [] as $set) {
                $reps = filled($set['reps'] ?? '') ? (int) $set['reps'] : null;
                $weight = filled($set['weight'] ?? '') ? (float) $set['weight'] : null;

                if ($reps === null && $weight === null) {
                    continue;
                }

                $sets[] = [
                    'reps' => $reps,
                    'weight' => $weight,
                    'weight_unit' => 'kg',
                    'notes' => filled($set['notes'] ?? '') ? $set['notes'] : null,
                ];
            }

            $normalized[] = [
                'name' => $name,
                'notes' => filled($exercise['notes'] ?? '') ? $exercise['notes'] : null,
                'sets' => $sets,
            ];
        }

        return $normalized;
    }

    /**
     * @return list<array{meal_type: string, meal_time: string|null, notes: string|null, items: list<array{name: string, quantity: float|null, unit: string|null, calories: int|null, protein_g: float|null}>}>
     */
    private function normalizeNutritionMeals(): array
    {
        $normalized = [];

        foreach ($this->nutritionMeals as $meal) {
            $items = [];

            foreach ($meal['items'] ?? [] as $item) {
                $name = trim((string) ($item['name'] ?? ''));

                if ($name === '') {
                    continue;
                }

                $items[] = [
                    'name' => $name,
                    'quantity' => filled($item['quantity'] ?? '') ? (float) $item['quantity'] : null,
                    'unit' => filled($item['unit'] ?? '') ? $item['unit'] : null,
                    'calories' => filled($item['calories'] ?? '') ? (int) $item['calories'] : null,
                    'protein_g' => filled($item['protein_g'] ?? '') ? (float) $item['protein_g'] : null,
                ];
            }

            if ($items === []) {
                continue;
            }

            $normalized[] = [
                'meal_type' => $meal['meal_type'],
                'meal_time' => filled($meal['meal_time'] ?? '') ? $meal['meal_time'] : null,
                'notes' => filled($meal['notes'] ?? '') ? $meal['notes'] : null,
                'items' => $items,
            ];
        }

        return $normalized;
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
        $this->workoutPlan = $this->hydrateWorkoutPlanForLocale(
            is_array($values['workout_plan'] ?? null) ? $values['workout_plan'] : [],
            $locale,
        );
        $this->mealPlanNotes = MissionLocalizedText::forLocale($values['meal_plan_notes'] ?? '', $locale);
        $this->equipmentNotes = MissionLocalizedText::forLocale($values['equipment_notes'] ?? '', $locale);
        $this->equipmentItems = $this->normalizeEquipmentItems($values['equipment_items'] ?? null);
        $this->registrationProgress = is_array($values['registration_progress'] ?? null)
            ? $values['registration_progress']
            : [];
    }

    private function ensureValidTab(): void
    {
        $allowed = ['schedule', 'workout', 'nutrition', 'supplements', 'equipment', 'registration', 'daily'];

        if (! in_array($this->activeTab, $allowed, true)) {
            $this->activeTab = 'workout';
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
        $this->aiInjuriesLimitations = (string) $defaults['injuries_limitations'];
        $this->aiDietaryPattern = (string) $defaults['dietary_pattern'];
        $this->aiCookingAbility = (string) $defaults['cooking_ability'];
        $this->aiAllergiesText = (string) $defaults['allergies_text'];
        $this->aiCoachingTone = (string) $defaults['coaching_tone'];
        $this->aiMotivationStyle = (string) $defaults['motivation_style'];
        $this->aiFavoriteExercisesText = (string) $defaults['favorite_exercises_text'];
        $this->aiDislikedExercisesText = (string) $defaults['disliked_exercises_text'];
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
            'training_experience' => $this->aiTrainingExperience,
            'primary_goal' => $this->aiPrimaryGoal,
            'training_days_per_week' => $this->aiTrainingDaysPerWeek,
            'session_duration' => $this->aiSessionDuration,
            'preferred_workout_time' => $this->aiPreferredWorkoutTime,
            'equipment' => $this->aiEquipment,
            'injuries_limitations' => $this->aiInjuriesLimitations,
            'dietary_pattern' => $this->aiDietaryPattern,
            'cooking_ability' => $this->aiCookingAbility,
            'allergies_text' => $this->aiAllergiesText,
            'coaching_tone' => $this->aiCoachingTone,
            'motivation_style' => $this->aiMotivationStyle,
            'favorite_exercises_text' => $this->aiFavoriteExercisesText,
            'disliked_exercises_text' => $this->aiDislikedExercisesText,
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function aiWizardRulesForStep(int $step): array
    {
        return match ($step) {
            1 => [
                'aiAge' => ['required', 'integer', 'min:16', 'max:80'],
                'aiGender' => $this->enumValueRule(Gender::class),
                'aiHeightCm' => ['required', 'integer', 'min:130', 'max:230'],
                'aiWeightKg' => ['required', 'numeric', 'min:35', 'max:250'],
                'aiBodyFatPercent' => ['nullable', 'numeric', 'min:3', 'max:60'],
            ],
            2 => [
                'aiTrainingExperience' => $this->enumValueRule(TrainingExperience::class),
                'aiPrimaryGoal' => $this->enumValueRule(PrimaryGoal::class),
                'aiTrainingDaysPerWeek' => ['required', 'integer', 'min:1', 'max:7'],
                'aiSessionDuration' => $this->enumValueRule(SessionDuration::class),
                'aiPreferredWorkoutTime' => $this->enumValueRule(WorkoutTimePreference::class),
                'aiEquipment' => $this->enumValueRule(EquipmentAccess::class),
                'aiInjuriesLimitations' => ['nullable', 'string', 'max:1000'],
            ],
            default => [
                'aiDietaryPattern' => $this->enumValueRule(DietaryPattern::class),
                'aiCookingAbility' => $this->enumValueRule(CookingAbility::class),
                'aiAllergiesText' => ['nullable', 'string', 'max:500'],
                'aiCoachingTone' => $this->enumValueRule(CoachingTone::class),
                'aiMotivationStyle' => $this->enumValueRule(MotivationStyle::class),
                'aiFavoriteExercisesText' => ['nullable', 'string', 'max:500'],
                'aiDislikedExercisesText' => ['nullable', 'string', 'max:500'],
            ],
        };
    }

    private function normalizeAiWizardInputs(): void
    {
        $this->aiGender = $this->normalizeBackedEnum($this->aiGender, Gender::class);
        $this->aiTrainingExperience = $this->normalizeBackedEnum($this->aiTrainingExperience, TrainingExperience::class);
        $this->aiPrimaryGoal = $this->normalizeBackedEnum($this->aiPrimaryGoal, PrimaryGoal::class);
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
            'goals' => $this->enumOptions(PrimaryGoal::cases(), 'missions.ai_goal_', $locale),
            'experience' => $this->enumOptions(TrainingExperience::cases(), 'missions.ai_experience_', $locale),
            'session_durations' => $this->enumOptions(SessionDuration::cases(), 'missions.ai_session_', $locale),
            'workout_times' => $this->enumOptions(WorkoutTimePreference::cases(), 'missions.ai_time_', $locale),
            'equipment' => $this->enumOptions(EquipmentAccess::cases(), 'missions.ai_equipment_', $locale),
            'dietary' => $this->enumOptions(DietaryPattern::cases(), 'missions.ai_diet_', $locale),
            'cooking' => $this->enumOptions(CookingAbility::cases(), 'missions.ai_cooking_', $locale),
            'tones' => $this->enumOptions(CoachingTone::cases(), 'missions.ai_tone_', $locale),
            'motivation' => $this->enumOptions(MotivationStyle::cases(), 'missions.ai_motivation_', $locale),
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

    private function dayKeyFromDate(string $date): string
    {
        $map = [
            'Saturday' => 'sat',
            'Sunday' => 'sun',
            'Monday' => 'mon',
            'Tuesday' => 'tue',
            'Wednesday' => 'wed',
            'Thursday' => 'thu',
            'Friday' => 'fri',
        ];

        $dayName = Carbon::parse($date)->format('l');

        return $map[$dayName] ?? 'sat';
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    /**
     * @param  list<array<string, mixed>>  $stored
     * @return list<array{day: string, focus: string, notes: string}>
     */
    private function hydrateWorkoutPlanForLocale(array $stored, string $locale): array
    {
        return array_values(array_map(
            static fn (array $row): array => [
                'day' => (string) ($row['day'] ?? 'sat'),
                'focus' => MissionLocalizedText::forLocale($row['focus'] ?? '', $locale),
                'notes' => MissionLocalizedText::forLocale($row['notes'] ?? '', $locale),
            ],
            $stored,
        ));
    }

    /**
     * @return list<array{day: string, focus: array{en: string, fa: string}, notes: array{en: string, fa: string}}>
     */
    private function persistWorkoutPlanRows(): array
    {
        $locale = app()->getLocale();
        $existing = $this->enrollment->field_values['workout_plan'] ?? [];
        $persisted = [];

        foreach ($this->workoutPlan as $index => $row) {
            $previous = is_array($existing[$index] ?? null) ? $existing[$index] : [];

            foreach ($existing as $existingRow) {
                if (is_array($existingRow) && ($existingRow['day'] ?? null) === ($row['day'] ?? null)) {
                    $previous = $existingRow;
                    break;
                }
            }

            $persisted[] = [
                'day' => (string) ($row['day'] ?? 'sat'),
                'focus' => MissionLocalizedText::merge($previous['focus'] ?? '', (string) ($row['focus'] ?? ''), $locale),
                'notes' => MissionLocalizedText::merge($previous['notes'] ?? '', (string) ($row['notes'] ?? ''), $locale),
            ];
        }

        return $persisted;
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
