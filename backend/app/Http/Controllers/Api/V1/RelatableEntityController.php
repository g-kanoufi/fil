<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\Api\ApiResponse;
use App\Support\Fields\FieldTypes;
use App\Support\Fields\RelatableEntities;
use Illuminate\Http\JsonResponse;

final class RelatableEntityController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $this->authorize('manageFields');

        return ApiResponse::payload([
            'types' => FieldTypes::all(),
            'relatable_entities' => RelatableEntities::catalogue(),
        ]);
    }
}
