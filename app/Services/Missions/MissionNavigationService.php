<?php

namespace App\Services\Missions;

use App\Models\User;
use App\Support\LocaleConfig;
use Modules\MissionEngine\Enums\MissionEnrollmentStatus;
use Modules\MissionEngine\Models\MissionEnrollment;

final class MissionNavigationService
{
    /**
     * @return array{
     *     href: string,
     *     catalog_href: string,
     *     active_count: int,
     *     primary_label: string,
     * }
     */
    public function forUser(?User $user, ?string $locale = null): array
    {
        $locale = LocaleConfig::resolve($locale ?? LocaleConfig::active());
        $catalogHref = route('missions.catalog', LocaleConfig::routeParameters([], $locale));

        if ($user === null) {
            return [
                'href' => $catalogHref,
                'catalog_href' => $catalogHref,
                'active_count' => 0,
                'primary_label' => __('nav.my_missions'),
            ];
        }

        $enrollments = MissionEnrollment::query()
            ->where('user_id', $user->id)
            ->where('status', MissionEnrollmentStatus::Active)
            ->orderByDesc('last_activity_at')
            ->get(['uuid']);

        $count = $enrollments->count();

        $href = match (true) {
            $count === 1 => route(
                'missions.workspace',
                LocaleConfig::routeParameters(['enrollment' => $enrollments->first()->uuid], $locale),
            ),
            $count > 1 => route('profile', LocaleConfig::routeParameters([], $locale)).'#missions',
            default => $catalogHref,
        };

        return [
            'href' => $href,
            'catalog_href' => $catalogHref,
            'active_count' => $count,
            'primary_label' => __('nav.my_missions'),
        ];
    }
}
