<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Jobs\Notifications\ProcessNotificationTriggerJob;
use App\Models\Lead;
use App\Models\NotificationRule;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

final class NotificationEmitterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_long_form_update_dispatches_notification_trigger(): void
    {
        Bus::fake([ProcessNotificationTriggerJob::class]);

        $user = User::factory()->create();
        $user->assignRole('franchisor');

        $lead = Lead::factory()->create(['form_data' => ['step' => 1]]);

        $this->actingAs($user)
            ->patchJson("/api/v1/leads/{$lead->id}", [
                'form_data' => ['step' => 2, 'company' => 'Acme'],
            ])
            ->assertOk();

        Bus::assertDispatched(ProcessNotificationTriggerJob::class, function (ProcessNotificationTriggerJob $job): bool {
            return $job->triggerSlug === 'application.long_form_updated';
        });
    }

    public function test_user_profile_update_dispatches_notification_trigger(): void
    {
        Bus::fake([ProcessNotificationTriggerJob::class]);

        $user = User::factory()->create(['first_name' => 'Pat']);

        $user->update(['first_name' => 'Patricia']);

        Bus::assertDispatched(ProcessNotificationTriggerJob::class, function (ProcessNotificationTriggerJob $job) use ($user): bool {
            return $job->triggerSlug === 'user.profile_updated'
                && $job->userId === $user->id;
        });
    }
}
