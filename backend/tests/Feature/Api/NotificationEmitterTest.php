<?php

declare(strict_types=1);
use App\Jobs\Notifications\ProcessNotificationTriggerJob;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});
test('long form update dispatches notification trigger', function () {
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
});
test('user profile update dispatches notification trigger', function () {
    Bus::fake([ProcessNotificationTriggerJob::class]);

    $user = User::factory()->create(['first_name' => 'Pat']);

    $user->update(['first_name' => 'Patricia']);

    Bus::assertDispatched(ProcessNotificationTriggerJob::class, function (ProcessNotificationTriggerJob $job) use ($user): bool {
        return $job->triggerSlug === 'user.profile_updated'
            && $job->userId === $user->id;
    });
});
