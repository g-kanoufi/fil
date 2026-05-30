<?php

declare(strict_types=1);
use App\Actions\Communications\SendStaffCommunication;
use App\Jobs\Communications\SendStaffCommunicationJob;
use App\Mail\DripStepMail;
use App\Models\Communication;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});
test('job delivers pending email communication', function () {
    Mail::fake();

    $staff = User::factory()->create();
    $staff->assignRole('admin');

    $prospect = User::factory()->create([
        'email' => 'prospect@example.com',
    ]);

    $lead = Lead::factory()->create([
        'prospect_user_id' => $prospect->id,
    ]);

    $send = app(SendStaffCommunication::class);
    $communication = $send->prepare(
        $lead,
        $staff,
        'email',
        'Queued hello',
        'Subject line',
    );

    expect($communication->status)->toBe('pending');

    (new SendStaffCommunicationJob($communication->id))->handle($send);

    $communication->refresh();
    expect($communication->status)->toBe('sent');
    Mail::assertSent(DripStepMail::class);
});
test('failed job marks communication failed', function () {
    $communication = Communication::query()->create([
        'lead_id' => Lead::factory()->create()->id,
        'type' => 'email',
        'direction' => 'outbound',
        'message' => 'Will fail',
        'status' => 'pending',
        'sent_at' => now(),
    ]);

    (new SendStaffCommunicationJob($communication->id))->failed(new RuntimeException('SMTP down'));

    $communication->refresh();
    expect($communication->status)->toBe('failed');
    expect($communication->errors)->toBe('SMTP down');
});
