<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Http\Resources\Api\V1\ProfileResource;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->authorize('viewSelf', $user);

        return ApiResponse::resource(new ProfileResource($user));
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $this->authorize('updateSelf', $user);

        $user->fill($request->profileAttributes());
        $user->save();

        return ApiResponse::resource(new ProfileResource($user->fresh()));
    }
}
