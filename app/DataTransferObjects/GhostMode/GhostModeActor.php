<?php

namespace App\DataTransferObjects\GhostMode;

use App\Models\User;

readonly class GhostModeActor
{
    public function __construct(
        public ?User $user,
        public ?string $guestToken,
    ) {}

    public function isAuthenticated(): bool
    {
        return $this->user !== null;
    }

    /**
     * @return array{user_id: ?int, guest_token: ?string}
     */
    public function toOwner(): array
    {
        return [
            'user_id' => $this->user?->id,
            'guest_token' => $this->isAuthenticated() ? null : $this->guestToken,
        ];
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array{user_id: ?int, guest_token: ?string, metadata: array<string, mixed>}
     */
    public function gamificationContext(array $metadata = []): array
    {
        return [
            'user_id' => $this->user?->id,
            'guest_token' => $this->isAuthenticated() ? null : $this->guestToken,
            'metadata' => $metadata,
        ];
    }
}
