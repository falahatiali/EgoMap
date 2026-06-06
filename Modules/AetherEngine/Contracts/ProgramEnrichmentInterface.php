<?php

namespace Modules\AetherEngine\Contracts;

use Modules\AetherEngine\Data\GeneratedProgramPayload;
use Modules\AetherEngine\Models\AetherUserProfile;

interface ProgramEnrichmentInterface
{
    /**
     * @return array<string, mixed>
     */
    public function enrich(AetherUserProfile $profile, GeneratedProgramPayload $payload): array;
}
