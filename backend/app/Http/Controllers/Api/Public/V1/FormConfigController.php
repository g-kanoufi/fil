<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Public\V1;

use App\Http\Controllers\Controller;
use App\Services\Portal\ProspectPortalConfig;
use App\Services\WidgetForms\WidgetFormConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FormConfigController extends Controller
{
    public function __construct(
        private readonly WidgetFormConfigService $widgetForms,
        private readonly ProspectPortalConfig $portal,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $siteKey = $request->input('site_key') ?? $request->header('X-FIL-Site-Key');

        $form = $this->widgetForms->resolveForSiteKey(is_string($siteKey) ? $siteKey : null);
        $descriptor = $this->widgetForms->publicDescriptor($form);

        return response()->json([
            'data' => [
                'form_key' => $descriptor['form_key'],
                'fields' => $descriptor['fields'],
                'version' => $descriptor['version'],
                'theme' => $descriptor['theme'],
                'recaptcha_site_key' => config('fil.recaptcha.site_key'),
                ...$this->portal->widgetMeta(),
            ],
        ]);
    }
}
