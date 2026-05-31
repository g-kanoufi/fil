<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\WidgetFormResource;
use App\Models\WidgetForm;
use App\Services\WidgetForms\EmbedSiteKeyGenerator;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

final class WidgetFormRotateSiteKeyController extends Controller
{
    public function __invoke(WidgetForm $widgetForm, EmbedSiteKeyGenerator $keys): JsonResponse
    {
        $this->authorize('manageFields');

        $widgetForm->site_key = $keys->generate();
        $widgetForm->save();

        return ApiResponse::resource(
            new WidgetFormResource($widgetForm->refresh()->load(['formFields.field'])),
        );
    }
}
