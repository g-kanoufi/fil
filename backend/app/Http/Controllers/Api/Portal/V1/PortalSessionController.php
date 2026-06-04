<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Portal\V1;

use App\Actions\Auth\LogoutUser;
use App\Actions\Portal\LoginProspect;
use App\Actions\Portal\SetupProspectPassword;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Portal\V1\LoginProspectRequest;
use App\Http\Requests\Api\Portal\V1\SetupProspectPasswordRequest;
use App\Http\Resources\Api\Portal\V1\PortalSessionResource;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class PortalSessionController extends Controller
{
    public function store(LoginProspectRequest $request, LoginProspect $loginProspect): JsonResponse
    {
        $user = $loginProspect->handle($request->credentials());

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return ApiResponse::resource(new PortalSessionResource($user));
    }

    public function show(Request $request): JsonResponse
    {
        return ApiResponse::resource(new PortalSessionResource($request->user()));
    }

    public function destroy(Request $request, LogoutUser $logoutUser): JsonResponse
    {
        $logoutUser->handle($request);

        return response()->json(null, 204);
    }

    public function setupPassword(SetupProspectPasswordRequest $request, SetupProspectPassword $setup): JsonResponse
    {
        $user = $setup->handle(
            (string) $request->input('token'),
            (string) $request->input('password'),
        );

        Auth::login($user);

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return ApiResponse::resource(new PortalSessionResource($user));
    }
}
