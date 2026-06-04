<?php

namespace Modules\MissionEngine\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Modules\MissionEngine\Enums\MissionActivityEvent;
use Modules\MissionEngine\Models\MissionActivityLog;
use Modules\MissionEngine\Models\MissionEnrollment;
use Modules\MissionEngine\Models\MissionSupplementIntake;
use Modules\MissionEngine\Models\MissionSupplementProduct;

final class MissionSupplementLogService
{
    /**
     * @param  array{name: string, brand?: string|null, default_unit?: string|null, default_amount?: string|null}  $data
     */
    public function addProduct(MissionEnrollment $enrollment, array $data): MissionSupplementProduct
    {
        $maxOrder = (int) MissionSupplementProduct::query()
            ->where('enrollment_id', $enrollment->id)
            ->max('sort_order');

        return MissionSupplementProduct::query()->create([
            'enrollment_id' => $enrollment->id,
            'name' => trim($data['name']),
            'brand' => $data['brand'] ?? null,
            'default_unit' => $data['default_unit'] ?? 'scoop',
            'default_amount' => $data['default_amount'] ?? null,
            'sort_order' => $maxOrder + 10,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array{
     *     intake_date: string,
     *     supplement_product_id?: int|null,
     *     product_name: string,
     *     brand?: string|null,
     *     amount: float,
     *     unit: string,
     *     intake_time?: string|null,
     *     taken?: bool,
     *     notes?: string|null,
     * }  $data
     */
    public function logIntake(MissionEnrollment $enrollment, User $user, array $data): MissionSupplementIntake
    {
        $intake = MissionSupplementIntake::query()->create([
            'enrollment_id' => $enrollment->id,
            'supplement_product_id' => $data['supplement_product_id'] ?? null,
            'product_name' => trim($data['product_name']),
            'brand' => $data['brand'] ?? null,
            'amount' => $data['amount'],
            'unit' => $data['unit'],
            'intake_date' => Carbon::parse($data['intake_date'])->toDateString(),
            'intake_time' => $data['intake_time'] ?? null,
            'taken' => $data['taken'] ?? true,
            'notes' => $data['notes'] ?? null,
        ]);

        $enrollment->touchActivity();

        MissionActivityLog::query()->create([
            'enrollment_id' => $enrollment->id,
            'user_id' => $user->id,
            'event_type' => MissionActivityEvent::SupplementLogged,
            'payload' => ['intake_uuid' => $intake->uuid],
            'logged_at' => now(),
        ]);

        return $intake;
    }

    /**
     * @return list<MissionSupplementProduct>
     */
    public function activeProducts(MissionEnrollment $enrollment): array
    {
        return MissionSupplementProduct::query()
            ->where('enrollment_id', $enrollment->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->all();
    }

    /**
     * @return LengthAwarePaginator<int, MissionSupplementIntake>
     */
    public function paginateIntakes(MissionEnrollment $enrollment, int $perPage = 15, string $pageName = 'supplementPage'): LengthAwarePaginator
    {
        return MissionSupplementIntake::query()
            ->where('enrollment_id', $enrollment->id)
            ->with('product')
            ->orderByDesc('intake_date')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], $pageName);
    }
}
