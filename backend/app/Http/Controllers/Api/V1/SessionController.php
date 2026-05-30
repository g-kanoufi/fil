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

final class SessionController extends Controller
{
    public function store(LoginRequest $request, LoginUser $loginUser, ActivityRecorder $activity): JsonResponse
    {
        $user = $loginUser->handle($request->credentials());

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
