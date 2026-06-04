<?php

declare(strict_types=1);

namespace App\Services\Portal;

use App\Models\ProspectPortalToken;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class ProspectPortalTokenService
{
    public function isEnabled(): bool
    {
        return (bool) config('fil-platform.portal.enabled', true);
    }

    /**
     * Issue a one-time setup token for password creation after widget intake.
     */
    public function issueSetupToken(User $user): ?string
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $plain = Str::random(64);

        ProspectPortalToken::query()->create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addHours((int) config('fil-platform.portal.setup_token_ttl_hours', 72)),
        ]);

        return $plain;
    }

    public function findValidUser(string $plainToken): ?User
    {
        $hash = hash('sha256', $plainToken);

        $record = ProspectPortalToken::query()
            ->where('token_hash', $hash)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        return $record?->user;
    }

    public function consumeSetupToken(User $user, string $plainToken, string $password): void
    {
        $hash = hash('sha256', $plainToken);

        $record = ProspectPortalToken::query()
            ->where('user_id', $user->id)
            ->where('token_hash', $hash)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($record === null) {
            abort(422, 'Invalid or expired setup token.');
        }

        $user->forceFill(['password' => Hash::make($password)])->save();

        $record->update(['used_at' => now()]);
    }
}
