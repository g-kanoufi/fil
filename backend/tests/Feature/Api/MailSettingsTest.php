<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\NotificationDelivery;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class MailSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_view_mail_settings_status(): void
    {
        $admin = User::factory()->create(['email' => 'admin@fil.test']);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->getJson('/api/v1/settings/mail')
            ->assertOk()
            ->assertJsonPath('data.mailer', config('mail.default'))
            ->assertJsonPath('data.from_address', config('mail.from.address'));
    }

    public function test_franchisor_cannot_view_mail_settings(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');

        $this->actingAs($user)
            ->getJson('/api/v1/settings/mail')
            ->assertForbidden();
    }

    public function test_admin_can_verify_mailgun_domain(): void
    {
        config([
            'mail.default' => 'mailgun',
            'services.mailgun.domain' => 'mg.example.com',
            'services.mailgun.secret' => 'key-test',
            'services.mailgun.endpoint' => 'api.mailgun.net',
        ]);

        Http::fake([
            'api.mailgun.net/v3/domains/mg.example.com' => Http::response([
                'domain' => ['state' => 'active'],
            ]),
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->postJson('/api/v1/settings/mail/verify')
            ->assertOk()
            ->assertJsonPath('data.mailgun.verified', true)
            ->assertJsonPath('data.status.configured', true);
    }

    public function test_admin_can_send_test_email(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['email' => 'admin@fil.test']);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->postJson('/api/v1/settings/mail/test')
            ->assertOk()
            ->assertJsonPath('data.sent', true)
            ->assertJsonPath('data.intended_recipient', 'admin@fil.test')
            ->assertJsonPath('data.recipient', 'mail-sink@fil.invalid');

        Mail::assertSent(\App\Mail\MailTestMail::class, function ($mail): bool {
            return $mail->hasTo('mail-sink@fil.invalid');
        });
    }
}
