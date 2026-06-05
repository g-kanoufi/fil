<?php

declare(strict_types=1);

namespace App\Actions\Demo;

use App\Models\Area;
use App\Models\Lead;
use App\Models\User;
use App\Services\Portal\ProspectPortalAccessService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

final class EnsureDemoProspectPortal
{
    public const PROSPECT_EMAIL = 'prospect@fil.test';

    public const LEAD_TITLE = 'Jane Smith Application';

    public function __construct(
        private readonly ProspectPortalAccessService $portalAccess,
    ) {}

    /**
     * @return array{prospect_id: int, lead_id: int, lead_title: string, can_access: bool}
     */
    public function handle(): array
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $prospect = User::query()->firstOrCreate(
            ['email' => self::PROSPECT_EMAIL],
            [
                'name' => 'Prospect Demo',
                'first_name' => 'Prospect',
                'last_name' => 'Demo',
                'password' => Hash::make('password'),
            ],
        );

        $prospect->forceFill([
            'name' => 'Prospect Demo',
            'first_name' => 'Prospect',
            'last_name' => 'Demo',
            'password' => Hash::make('password'),
        ])->save();

        $prospect->syncRoles(['prospect']);

        $areaId = Area::query()->where('slug', 'southwest')->value('id');
        $ownerId = User::query()->where('email', 'owner@fil.test')->value('id');

        $lead = Lead::query()->firstOrCreate(
            ['title' => self::LEAD_TITLE],
            [
                'lead_status' => '1',
                'lead_fdd_status' => 'active',
                'lead_temp' => 'hot',
                'lead_source' => 'widget',
                'pipeline_phase' => 1,
                'owner_user_id' => $ownerId,
                'area_id' => $areaId,
                'lead_stage' => '1',
                'likelihood_to_close' => 80,
                'status' => 'active',
            ],
        );

        $lead->forceFill([
            'prospect_user_id' => $prospect->id,
            'pipeline_phase' => $lead->pipeline_phase === 99 ? 1 : ($lead->pipeline_phase ?? 1),
            'status' => 'active',
        ])->save();

        return [
            'prospect_id' => $prospect->id,
            'lead_id' => $lead->id,
            'lead_title' => $lead->title,
            'can_access' => Gate::forUser($prospect->fresh())->allows('accessProspectPortal'),
        ];
    }
}
