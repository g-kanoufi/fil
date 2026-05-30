<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Actions\Communications\SendStaffCommunication;
use App\Jobs\Communications\SendStaffCommunicationJob;
use App\Models\Communication;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class SendStaffCommunicationJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_job_delivers_pending_email_communication(): void
    {
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

        $this->assertSame('pending', $communication->status);

        (new SendStaffCommunicationJob($communication->id))->handle($send);

        $communication->refresh();
        $this->assertSame('sent', $communication->status);
        Mail::assertSent(\App\Mail\DripStepMail::class);
    }

    public function test_failed_job_marks_communication_failed(): void
    {
        $communication = Communication::query()->create([
            'lead_id' => Lead::factory()->create()->id,
            'type' => 'email',
            'direction' => 'outbound',
            'message' => 'Will fail',
            'status' => 'pending',
            'sent_at' => now(),
        ]);

        (new SendStaffCommunicationJob($communication->id))->failed(new \RuntimeException('SMTP down'));

        $communication->refresh();
        $this->assertSame('failed', $communication->status);
        $this->assertSame('SMTP down', $communication->errors);
    }
}
