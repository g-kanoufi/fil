<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\AchCustomer;
use App\Models\AchFundingSource;
use App\Models\Document;
use App\Models\DocumentLink;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LegacyImportDocumentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_import_command_imports_store_documents_and_links(): void
    {
        $posts = base_path('tests/fixtures/legacy-posts.sql');
        $documents = base_path('tests/fixtures/legacy-documents.sql');

        $this->artisan('legacy:import', [
            'dump' => $posts,
            '--prefix' => 'wp_9_',
            '--only' => 'stores',
            '--execute' => true,
        ])->assertSuccessful();

        $this->artisan('legacy:import', [
            'dump' => $documents,
            '--prefix' => 'wp_9_',
            '--only' => 'documents',
            '--execute' => true,
        ])->assertSuccessful();

        $store = Store::query()->where('legacy_post_id', 202)->first();
        $this->assertNotNull($store);

        $this->assertSame(1, Document::query()->where('legacy_post_id', 5001)->count());
        $this->assertSame(1, DocumentLink::query()
            ->where('linkable_type', Store::class)
            ->where('linkable_id', $store->id)
            ->where('role', 'doctors_license')
            ->count());
    }

    public function test_import_command_imports_ach_enrollment_from_store_meta(): void
    {
        $posts = base_path('tests/fixtures/legacy-posts.sql');
        $documents = base_path('tests/fixtures/legacy-documents.sql');

        $this->artisan('legacy:import', [
            'dump' => $posts,
            '--prefix' => 'wp_9_',
            '--only' => 'stores',
            '--execute' => true,
        ])->assertSuccessful();

        $this->artisan('legacy:import', [
            'dump' => $documents,
            '--prefix' => 'wp_9_',
            '--only' => 'ach_enrollment',
            '--execute' => true,
        ])->assertSuccessful();

        $store = Store::query()->where('legacy_post_id', 202)->first();
        $this->assertNotNull($store);

        $this->assertDatabaseHas('ach_customers', [
            'owner_type' => Store::class,
            'owner_id' => $store->id,
            'external_customer_id' => 'cust-legacy-abc',
        ]);

        $customer = AchCustomer::query()->where('owner_id', $store->id)->first();
        $this->assertNotNull($customer);
        $this->assertSame(1, AchFundingSource::query()->where('ach_customer_id', $customer->id)->count());
    }

    public function test_import_command_imports_franchise_location_documents(): void
    {
        $posts = base_path('tests/fixtures/legacy-posts.sql');
        $documents = base_path('tests/fixtures/legacy-documents.sql');

        $this->artisan('legacy:import', [
            'dump' => $posts,
            '--prefix' => 'wp_9_',
            '--only' => 'franchise_locations,stores',
            '--execute' => true,
        ])->assertSuccessful();

        $this->artisan('legacy:import', [
            'dump' => $documents,
            '--prefix' => 'wp_9_',
            '--only' => 'documents',
            '--execute' => true,
        ])->assertSuccessful();

        $location = \App\Models\FranchiseLocation::query()->where('legacy_post_id', 303)->first();
        $this->assertNotNull($location);

        $this->assertSame(1, DocumentLink::query()
            ->where('linkable_type', \App\Models\FranchiseLocation::class)
            ->where('linkable_id', $location->id)
            ->where('role', 'pre-lease_loi_documents')
            ->count());
    }

    public function test_import_command_imports_user_medical_certification_when_user_exists(): void
    {
        $users = base_path('tests/fixtures/legacy-users.sql');
        $documents = base_path('tests/fixtures/legacy-documents.sql');

        $this->artisan('legacy:import', [
            'dump' => $users,
            '--prefix' => 'wp_9_',
            '--only' => 'users',
            '--execute' => true,
        ])->assertSuccessful();

        $this->artisan('legacy:import', [
            'dump' => $documents,
            '--prefix' => 'wp_9_',
            '--only' => 'documents',
            '--execute' => true,
        ])->assertSuccessful();

        $user = User::query()->where('legacy_user_id', 502)->first();
        $this->assertNotNull($user);

        $this->assertSame(1, DocumentLink::query()
            ->where('linkable_type', User::class)
            ->where('linkable_id', $user->id)
            ->where('role', 'medical_certification')
            ->count());
    }
}
