<?php

namespace App\Livewire\Community;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Modules\CommunityEngine\Services\CommunityPostService;

class CreatePost extends Component
{
    public string $content = '';

    public bool $isAnonymous = false;

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:5', 'max:1000'],
            'isAnonymous' => ['boolean'],
        ];
    }

    public function submit(): void
    {
        abort_unless(Auth::check(), 403);

        $this->validate();

        $result = app(CommunityPostService::class)->create(
            user: Auth::user(),
            content: $this->content,
            isAnonymous: $this->isAnonymous,
        );

        if ($result['rejected']) {
            $this->addError('content', $result['message']);

            return;
        }

        $this->reset('content', 'isAnonymous');
        $this->dispatch('post-created');
    }

    public function render(): View
    {
        return view('livewire.community.create-post');
    }
}
