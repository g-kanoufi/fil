<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\LoginUser;
use App\Actions\Auth\LogoutUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Resources\Api\V1\SessionResource;
use App\Services\Activity\ActivityRecorder;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class SessionController extends Controller
{
    /** Per-email + IP lockout threshold (prod/staging only). */
    private const MAX_FAILED_ATTEMPTS = 5;

    public function store(LoginRequest $request, LoginUser $loginUser, ActivityRecorder $activity): JsonResponse
    {
        $email = (string) $request->input('email');

        $this->ensureNotLockedOut($request, $email);

        try {
            $user = $loginUser->handle($request->credentials());
        } catch (ValidationException $exception) {
            $activity->record(
                category: 'auth',
                action: 'login_failed',
                summary: sprintf('Failed sign-in attempt for %s', $email),
            );

            if ($this->lockoutEnabled()) {
                RateLimiter::hit($this->lockoutKey($request, $email));
            }

            throw $exception;
        }

        if ($this->lockoutEnabled()) {
            RateLimiter::clear($this->lockoutKey($request, $email));
        }

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $activity->record(
            category: 'auth',
            action: 'login',
            summary: sprintf('%s signed in', $user->name),
            actor: $user,
            subject: $user,
        );

        return ApiResponse::resource(new SessionResource($user));
    }

    private function lockoutEnabled(): bool
    {
        return app()->environment('production', 'staging');
    }

    private function lockoutKey(Request $request, string $email): string
    {
        return 'staff-login:'.Str::lower($email).'|'.$request->ip();
    }

    private function ensureNotLockedOut(Request $request, string $email): void
    {
        if (! $this->lockoutEnabled()) {
            return;
        }

        $key = $this->lockoutKey($request, $email);

        if (RateLimiter::tooManyAttempts($key, self::MAX_FAILED_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'email' => [__('Too many login attempts. Please try again in :seconds seconds.', [
                    'seconds' => RateLimiter::availableIn($key),
                ])],
            ])->status(429);
        }
    }

    public function show(Request $request): JsonResponse
    {
        return ApiResponse::resource(new SessionResource($request->user()));
    }

    public function destroy(Request $request, LogoutUser $logoutUser, ActivityRecorder $activity): JsonResponse
    {
        $user = $request->user();

        if ($user !== null) {
            $activity->record(
                category: 'auth',
                action: 'logout',
                summary: sprintf('%s signed out', $user->name),
                actor: $user,
                subject: $user,
            );
        }

        $logoutUser->handle($request);

        return response()->json(null, 204);
    }
}
