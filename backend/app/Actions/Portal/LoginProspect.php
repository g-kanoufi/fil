<?php

declare(strict_types=1);

namespace App\Actions\Portal;

use App\Models\User;
use App\Services\Auth\UserAccessService;
use App\Services\Portal\ProspectPortalAccessService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

final class LoginProspect
{
    public function __construct(
        private readonly UserAccessService $access,
        private readonly ProspectPortalAccessService $portalAccess,
    ) {}

    /**
     * @param  array{email: string, password: string}  $credentials
     */
    public function handle(array $credentials): User
    {
        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => [__('The provided credentials are incorrect.')],
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        if (! $user->hasRole('prospect', 'web') || $this->access->canAccessStaffApp($user)) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => [__('This account cannot access the prospect portal.')],
            ]);
        }

        if (! $this->portalAccess->canAccessPortal($user)) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => [__('No active application is linked to this account.')],
            ]);
        }

        return $user;
    }
}
