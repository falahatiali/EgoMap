<?php

namespace Modules\MissionEngine\Models;

use App\Models\Concerns\HasAppTranslations;
use App\Observers\AssignsUuidObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'name', 'description', 'icon', 'sort_order', 'is_active'])]
#[ObservedBy([AssignsUuidObserver::class])]
class MissionCategory extends Model
{
    use HasAppTranslations;

    /** @var list<string> */
    public array $translatable = ['name', 'description'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<MissionTemplate, $this>
     */
    public function templates(): HasMany
    {
        return $this->hasMany(MissionTemplate::class, 'category_id');
    }
}
