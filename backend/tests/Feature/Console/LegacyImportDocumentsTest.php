<?php

declare(strict_types=1);
use App\Models\AchCustomer;
use App\Models\AchFundingSource;
use App\Models\Document;
use App\Models\DocumentLink;
use App\Models\FranchiseLocation;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});
test('import command imports store documents and links', function () {
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
    expect($store)->not->toBeNull();

    expect(Document::query()->where('legacy_post_id', 5001)->count())->toBe(1);
    expect(DocumentLink::query()
        ->where('linkable_type', Store::class)
        ->where('linkable_id', $store->id)
        ->where('role', 'doctors_license')
        ->count())->toBe(1);
});
test('import command imports ach enrollment from store meta', function () {
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
    expect($store)->not->toBeNull();

    $this->assertDatabaseHas('ach_customers', [
        'owner_type' => Store::class,
        'owner_id' => $store->id,
        'external_customer_id' => 'cust-legacy-abc',
    ]);

    $customer = AchCustomer::query()->where('owner_id', $store->id)->first();
    expect($customer)->not->toBeNull();
    expect(AchFundingSource::query()->where('ach_customer_id', $customer->id)->count())->toBe(1);
});
test('import command imports franchise location documents', function () {
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

    $location = FranchiseLocation::query()->where('legacy_post_id', 303)->first();
    expect($location)->not->toBeNull();

    expect(DocumentLink::query()
        ->where('linkable_type', FranchiseLocation::class)
        ->where('linkable_id', $location->id)
        ->where('role', 'pre-lease_loi_documents')
        ->count())->toBe(1);
});
test('import command imports user medical certification when user exists', function () {
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
    expect($user)->not->toBeNull();

    expect(DocumentLink::query()
        ->where('linkable_type', User::class)
        ->where('linkable_id', $user->id)
        ->where('role', 'medical_certification')
        ->count())->toBe(1);
});
