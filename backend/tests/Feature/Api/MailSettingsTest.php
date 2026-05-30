<?php

declare(strict_types=1);
use App\Mail\MailTestMail;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});
test('admin can view mail settings status', function () {
    $admin = User::factory()->create(['email' => 'admin@fil.test']);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->getJson('/api/v1/settings/mail')
        ->assertOk()
        ->assertJsonPath('data.mailer', config('mail.default'))
        ->assertJsonPath('data.from_address', config('mail.from.address'));
});
test('franchisor cannot view mail settings', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');

    $this->actingAs($user)
        ->getJson('/api/v1/settings/mail')
        ->assertForbidden();
});
test('admin can verify mailgun domain', function () {
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
});
test('admin can send test email', function () {
    Mail::fake();

    $admin = User::factory()->create(['email' => 'admin@fil.test']);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->postJson('/api/v1/settings/mail/test')
        ->assertOk()
        ->assertJsonPath('data.sent', true)
        ->assertJsonPath('data.intended_recipient', 'admin@fil.test')
        ->assertJsonPath('data.recipient', 'mail-sink@fil.invalid');

    Mail::assertSent(MailTestMail::class, function ($mail): bool {
        return $mail->hasTo('mail-sink@fil.invalid');
    });
});
