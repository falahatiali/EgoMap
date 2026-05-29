<?php

namespace App\Livewire\Missions;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\MissionEngine\Enums\MissionTemplateStatus;
use Modules\MissionEngine\Models\MissionEnrollment;
use Modules\MissionEngine\Models\MissionTemplate;

#[Layout('layouts.app')]
class Catalog extends Component
{
    public function render(): View
    {
        $locale = app()->getLocale();
        $user = auth()->user();

        /** @var Collection<int, MissionTemplate> $templates */
        $templates = MissionTemplate::query()
            ->with('category')
            ->where('status', MissionTemplateStatus::Published)
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->get();

        $enrollmentsByTemplate = collect();

        if ($user !== null) {
            $enrollmentsByTemplate = MissionEnrollment::query()
                ->where('user_id', $user->id)
                ->whereIn('template_id', $templates->pluck('id'))
                ->get()
                ->keyBy('template_id');
        }

        return view('livewire.missions.catalog', [
            'templates' => $templates,
            'enrollmentsByTemplate' => $enrollmentsByTemplate,
            'locale' => $locale,
        ]);
    }
}
