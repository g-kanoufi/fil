<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use App\Services\App\ClientOptionsService;
use App\Services\App\MenuStructureService;
use App\Services\Leads\LeadPipelineCatalog;
use App\Services\Leads\LeadStatusMenuService;
use App\Services\Platform\PlatformStageCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
final class AppConfigResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ClientOptionsService $options */
        $options = app(ClientOptionsService::class);
        /** @var MenuStructureService $menus */
        $menus = app(MenuStructureService::class);
        /** @var LeadPipelineCatalog $pipeline */
        $pipeline = app(LeadPipelineCatalog::class);
        /** @var LeadStatusMenuService $leadStatuses */
        $leadStatuses = app(LeadStatusMenuService::class);
        /** @var PlatformStageCatalog $platformStages */
        $platformStages = app(PlatformStageCatalog::class);

        return [
            'options' => $options->forStaffApp(),
            'menus' => $menus->forStaffApp(),
            'lead_application_status' => $leadStatuses->forAppConfig(),
            'pipeline' => [
                'phases' => collect($pipeline->phases())
                    ->map(fn (array $phase, int $key) => [
                        'id' => $key,
                        'label' => $phase['label'],
                        'description' => $phase['description'],
                        'platform_stage' => $platformStages->stageForPipelinePhase($key),
                    ])
                    ->values()
                    ->all(),
            ],
            'platform_stages' => $platformStages->forAppConfig(),
        ];
    }
}
