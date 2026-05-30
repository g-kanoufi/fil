<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use App\Services\App\ClientOptionsService;
use App\Services\App\MenuStructureService;
use App\Services\Leads\LeadPipelineCatalog;
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

        return [
            'options' => $options->forStaffApp(),
            'menus' => $menus->forStaffApp(),
            'pipeline' => [
                'phases' => collect($pipeline->phases())
                    ->map(fn (array $phase, int $key) => [
                        'id' => $key,
                        'label' => $phase['label'],
                        'description' => $phase['description'],
                    ])
                    ->values()
                    ->all(),
            ],
        ];
    }
}
