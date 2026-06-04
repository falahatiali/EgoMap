<?php

namespace Modules\MissionEngine\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\MissionEngine\Enums\CaloriesStatus;
use Modules\MissionEngine\Enums\MealType;
use Modules\MissionEngine\Enums\MissionActivityEvent;
use Modules\MissionEngine\Models\MissionActivityLog;
use Modules\MissionEngine\Models\MissionEnrollment;
use Modules\MissionEngine\Models\MissionMeal;
use Modules\MissionEngine\Models\MissionNutritionDay;

final class MissionNutritionLogService
{
    /**
     * @param  array{
     *     log_date: string,
     *     day_notes?: string|null,
     *     meal_quality_score?: int|null,
     *     meals: list<array{
     *         meal_type: string,
     *         meal_time?: string|null,
     *         notes?: string|null,
     *         items: list<array{name: string, quantity?: float|null, unit?: string|null, calories?: int|null, protein_g?: float|null}>
     *     }>
     * }  $data
     */
    public function saveDay(MissionEnrollment $enrollment, User $user, array $data): MissionNutritionDay
    {
        return DB::transaction(function () use ($enrollment, $user, $data): MissionNutritionDay {
            $logDate = Carbon::parse($data['log_date'])->toDateString();

            $day = MissionNutritionDay::query()
                ->where('enrollment_id', $enrollment->id)
                ->whereDate('log_date', $logDate)
                ->first();

            if ($day === null) {
                $day = MissionNutritionDay::query()->create([
                    'enrollment_id' => $enrollment->id,
                    'log_date' => $logDate,
                    'day_notes' => $data['day_notes'] ?? null,
                    'meal_quality_score' => $data['meal_quality_score'] ?? null,
                ]);
            } else {
                $day->update([
                    'day_notes' => $data['day_notes'] ?? null,
                    'meal_quality_score' => $data['meal_quality_score'] ?? null,
                ]);
            }

            $day->meals()->each(function (MissionMeal $meal): void {
                $meal->items()->delete();
            });
            $day->meals()->delete();

            $totalCalories = 0;
            $mealOrder = 0;

            foreach ($data['meals'] as $mealData) {
                $mealType = MealType::tryFrom((string) ($mealData['meal_type'] ?? '')) ?? MealType::Snack;

                $meal = $day->meals()->create([
                    'meal_type' => $mealType,
                    'meal_time' => $mealData['meal_time'] ?? null,
                    'notes' => $mealData['notes'] ?? null,
                    'sort_order' => ($mealOrder + 1) * 10,
                ]);
                $mealOrder++;

                $mealCalories = 0;

                foreach ($mealData['items'] ?? [] as $itemIndex => $itemData) {
                    $name = trim((string) ($itemData['name'] ?? ''));

                    if ($name === '') {
                        continue;
                    }

                    $itemCalories = isset($itemData['calories']) ? (int) $itemData['calories'] : null;
                    $mealCalories += $itemCalories ?? 0;

                    $meal->items()->create([
                        'name' => $name,
                        'quantity' => $itemData['quantity'] ?? null,
                        'unit' => $itemData['unit'] ?? null,
                        'calories' => $itemCalories,
                        'protein_g' => $itemData['protein_g'] ?? null,
                        'sort_order' => ($itemIndex + 1) * 10,
                    ]);
                }

                if ($mealCalories > 0) {
                    $meal->update(['meal_calories' => $mealCalories]);
                    $totalCalories += $mealCalories;
                }
            }

            $day->update([
                'total_calories' => $totalCalories > 0 ? $totalCalories : null,
                'calories_status' => $totalCalories > 0 ? CaloriesStatus::Unknown : null,
            ]);

            $enrollment->touchActivity();

            MissionActivityLog::query()->create([
                'enrollment_id' => $enrollment->id,
                'user_id' => $user->id,
                'event_type' => MissionActivityEvent::NutritionLogged,
                'payload' => [
                    'nutrition_day_uuid' => $day->uuid,
                    'log_date' => $logDate,
                    'total_calories' => $totalCalories,
                ],
                'logged_at' => now(),
            ]);

            return $day->load(['meals.items']);
        });
    }

    /**
     * @return LengthAwarePaginator<int, MissionNutritionDay>
     */
    public function paginateDays(MissionEnrollment $enrollment, int $perPage = 10, string $pageName = 'nutritionPage'): LengthAwarePaginator
    {
        return MissionNutritionDay::query()
            ->where('enrollment_id', $enrollment->id)
            ->with(['meals.items'])
            ->orderByDesc('log_date')
            ->paginate($perPage, ['*'], $pageName);
    }

    public function findDayForDate(MissionEnrollment $enrollment, string $date): ?MissionNutritionDay
    {
        return MissionNutritionDay::query()
            ->where('enrollment_id', $enrollment->id)
            ->whereDate('log_date', Carbon::parse($date)->toDateString())
            ->with(['meals.items'])
            ->first();
    }
}
