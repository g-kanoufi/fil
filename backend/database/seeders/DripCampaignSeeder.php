<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\DripCampaign;
use App\Models\DripStep;
use Illuminate\Database\Seeder;

final class DripCampaignSeeder extends Seeder
{
    public function run(): void
    {
        $campaign = DripCampaign::query()->updateOrCreate(
            ['slug' => 'new-lead-welcome'],
            [
                'name' => 'New Lead Welcome',
                'description' => 'Default welcome sequence for widget leads',
                'status' => 'active',
                'trigger_event' => 'lead_created',
            ],
        );

        DripStep::query()->updateOrCreate(
            ['drip_campaign_id' => $campaign->id, 'sort_order' => 1],
            [
                'delay_days' => 0,
                'delay_hours' => 0,
                'channel' => 'email',
                'subject' => 'Welcome to our franchise process',
                'body_template' => 'Thank you for your interest. A team member will be in touch soon.',
                'status' => 'active',
            ],
        );
    }
}
