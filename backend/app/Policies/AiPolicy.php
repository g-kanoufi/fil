<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\AiAssistant;
use App\Models\User;
use App\Policies\Concerns\ChecksFilPermissions;

final class AiPolicy
{
    use ChecksFilPermissions;

    public function access(User $user, AiAssistant $assistant = new AiAssistant): bool
    {
        return $this->allows($user, 'ai.use');
    }
}
