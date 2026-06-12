<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified' => $this->email_verified_at !== null,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'member_since' => $this->created_at?->toIso8601String(),
            'member_since_label' => $this->created_at !== null
                ? __('profile.member_since', [
                    'date' => $this->created_at->locale(app()->getLocale())->translatedFormat('M Y'),
                ])
                : null,
            'recovery_phase' => $this->recovery_phase,
            'recovery_triage_completed' => $this->recovery_triage_completed_at !== null,
        ];
    }
}
