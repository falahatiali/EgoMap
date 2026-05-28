<?php

namespace App\Livewire\Admin\Quizzes;

use App\Enums\Permission;
use App\Enums\QuizType;
use App\Livewire\Admin\Concerns\WithAdminPage;
use App\Models\Quiz;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Edit extends Component
{
    use WithAdminPage;

    public Quiz $quiz;

    public string $slug = '';

    public string $type = '';

    public string $nameEn = '';

    public string $descriptionEn = '';

    public bool $isActive = true;

    public int $estimatedMinutes = 10;

    public int $version = 1;

    public function mount(Quiz $quiz): void
    {
        abort_unless(auth()->user()?->can(Permission::AdminQuizzesManage->value), 403);

        $this->quiz = $quiz;
        $this->slug = $quiz->slug;
        $this->type = $quiz->type->value;
        $this->nameEn = (string) $quiz->getTranslation('name', 'en', true);
        $this->descriptionEn = (string) ($quiz->getTranslation('description', 'en', true) ?? '');
        $this->isActive = $quiz->is_active;
        $this->estimatedMinutes = (int) $quiz->estimated_minutes;
        $this->version = (int) $quiz->version;
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->can(Permission::AdminQuizzesManage->value), 403);

        $this->validate([
            'slug' => ['required', 'string', 'max:120', Rule::unique('quizzes', 'slug')->ignore($this->quiz->id)],
            'type' => ['required', Rule::enum(QuizType::class)],
            'nameEn' => ['required', 'string', 'max:500'],
            'descriptionEn' => ['nullable', 'string', 'max:5000'],
            'isActive' => ['boolean'],
            'estimatedMinutes' => ['required', 'integer', 'min:1', 'max:180'],
            'version' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        $this->quiz->update([
            'slug' => $this->slug,
            'type' => $this->type,
            'is_active' => $this->isActive,
            'estimated_minutes' => $this->estimatedMinutes,
            'version' => $this->version,
        ]);

        $this->quiz->setTranslation('name', 'en', $this->nameEn);
        $this->quiz->setTranslation('description', 'en', $this->descriptionEn);
        $this->quiz->save();

        $this->adminFlash(__('admin.quizzes.saved'));
    }

    public function render(): View
    {
        return $this->adminView('livewire.admin.quizzes.edit', [
            'typeOptions' => QuizType::cases(),
            'questionsCount' => $this->quiz->questions()->count(),
            'sessionsCount' => $this->quiz->sessions()->count(),
        ], 'quizzes');
    }
}
