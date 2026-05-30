<?php

declare(strict_types=1);
use App\Models\Area;
use App\Models\StoreOwner;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('seeds franchise role matrix users and assignments', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(DemoSeeder::class);

    $franchisee = User::query()->where('email', 'franchisee@fil.test')->firstOrFail();
    $areaRep = User::query()->where('email', 'area_rep@fil.test')->firstOrFail();
    $prospect = User::query()->where('email', 'prospect@fil.test')->firstOrFail();

    expect($franchisee->hasRole('franchisee'))->toBeTrue();
    expect($areaRep->hasRole('area_rep'))->toBeTrue();
    expect($prospect->hasRole('prospect'))->toBeTrue();

    $area = Area::query()->where('slug', 'southwest')->firstOrFail();
    expect((int) data_get($area->extras, 'rep_user_id'))->toBe($areaRep->id);

    expect(StoreOwner::query()
        ->where('user_id', $franchisee->id)
        ->whereHas('store', fn ($query) => $query->where('slug', 'primeiv-scottsdale'))
        ->exists())->toBeTrue();

    $this->assertDatabaseCount('leads', 4);
});
