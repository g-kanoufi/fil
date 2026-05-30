<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Public;

use App\Models\ClientSetting;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UiAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BrandingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(UiAccessSeeder::class);
    }

    public function test_public_branding_returns_client_settings_without_auth(): void
    {
        ClientSetting::query()->updateOrCreate(
            ['key' => 'brandName'],
            ['value' => 'Acme Franchise'],
        );
        ClientSetting::query()->updateOrCreate(
            ['key' => 'highlightColor'],
            ['value' => '#0f766e'],
        );
        ClientSetting::query()->updateOrCreate(
            ['key' => 'logoUrl'],
            ['value' => 'https://cdn.example.com/logo.svg'],
        );

        $this->getJson('/api/public/v1/branding')
            ->assertOk()
            ->assertJsonPath('data.brandName', 'Acme Franchise')
            ->assertJsonPath('data.highlightColor', '#0f766e')
            ->assertJsonPath('data.logoUrl', 'https://cdn.example.com/logo.svg')
            ->assertJsonPath('data.clientBranding', true);
    }
}
