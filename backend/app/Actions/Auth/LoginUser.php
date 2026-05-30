<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\Auth\UserAccessService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

final class LoginUser
{
    public function __construct(
        private readonly UserAccessService $access,
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

        if (! $this->access->canAccessStaffApp($user)) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => [__('This account cannot access the staff application.')],
            ]);
        }

        return $user;
    }
}
