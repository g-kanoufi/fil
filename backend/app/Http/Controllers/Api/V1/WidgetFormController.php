<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreWidgetFormRequest;
use App\Http\Requests\Api\V1\UpdateWidgetFormRequest;
use App\Http\Resources\Api\V1\WidgetFormResource;
use App\Models\WidgetForm;
use App\Services\WidgetForms\EmbedSiteKeyGenerator;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

final class WidgetFormController extends Controller
{
    public function __construct(
        private readonly EmbedSiteKeyGenerator $siteKeys,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('manageFields');

        $forms = WidgetForm::query()
            ->with(['formFields.field'])
            ->orderBy('name')
            ->get();

        return ApiResponse::collection(WidgetFormResource::collection($forms));
    }

    public function show(WidgetForm $widgetForm): JsonResponse
    {
        $this->authorize('manageFields');

        return ApiResponse::resource(
            new WidgetFormResource($widgetForm->load(['formFields.field'])),
        );
    }

    public function store(StoreWidgetFormRequest $request): JsonResponse
    {
        $siteKey = $request->validated('site_key');

        if (! is_string($siteKey) || trim($siteKey) === '') {
            $siteKey = $this->siteKeys->generate();
        }

        $form = WidgetForm::query()->create([
            'key' => $request->validated('key'),
            'name' => $request->validated('name'),
            'site_key' => $siteKey,
            'entity' => $request->validated('entity', 'lead'),
            'status' => $request->validated('status', 'active'),
            'settings' => $request->validated('settings'),
        ]);

        return ApiResponse::resource(
            new WidgetFormResource($form->load(['formFields.field'])),
            201,
        );
    }

    public function update(UpdateWidgetFormRequest $request, WidgetForm $widgetForm): JsonResponse
    {
        $widgetForm->fill($request->validated());
        $widgetForm->save();

        return ApiResponse::resource(
            new WidgetFormResource($widgetForm->refresh()->load(['formFields.field'])),
        );
    }
}
