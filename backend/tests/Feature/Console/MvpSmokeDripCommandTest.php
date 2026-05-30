<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Database\Seeders\DripCampaignSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class MvpSmokeDripCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(DripCampaignSeeder::class);
    }

    public function test_smoke_drip_command_sends_through_mailhog_sink(): void
    {
        Http::fake(function () {
            static $calls = 0;
            $calls++;

            return Http::response(['total' => $calls >= 3 ? 1 : 0], 200);
        });

        $this->artisan('mvp:smoke-drip')
            ->assertSuccessful()
            ->expectsOutputToContain('Drip step sent');

        $this->assertDatabaseHas('communications', [
            'type' => 'email',
            'status' => 'sent',
        ]);
    }
}
