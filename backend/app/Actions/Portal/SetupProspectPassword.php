<?php

declare(strict_types=1);

namespace App\Actions\Portal;

use App\Models\User;
use App\Services\Portal\ProspectPortalTokenService;
use Illuminate\Validation\ValidationException;

final class SetupProspectPassword
{
    public function __construct(
        private readonly ProspectPortalTokenService $tokens,
    ) {}

    public function handle(string $token, string $password): User
    {
        $user = $this->tokens->findValidUser($token);

        if ($user === null) {
            throw ValidationException::withMessages([
                'token' => [__('Invalid or expired setup link.')],
            ]);
        }

        if (! $user->hasRole('prospect')) {
            throw ValidationException::withMessages([
                'token' => [__('This setup link is not valid.')],
            ]);
        }

        $this->tokens->consumeSetupToken($user, $token, $password);

        return $user->fresh() ?? $user;
    }
}
