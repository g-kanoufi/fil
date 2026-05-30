<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\WidgetForms\SyncWidgetFormFields;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SyncWidgetFormFieldsRequest;
use App\Http\Resources\Api\V1\WidgetFormResource;
use App\Models\WidgetForm;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

final class WidgetFormFieldSyncController extends Controller
{
    public function __invoke(
        SyncWidgetFormFieldsRequest $request,
        WidgetForm $widgetForm,
        SyncWidgetFormFields $sync,
    ): JsonResponse {
        $form = $sync->handle($widgetForm, $request->fieldsPayload());

        return ApiResponse::resource(new WidgetFormResource($form));
    }
}
