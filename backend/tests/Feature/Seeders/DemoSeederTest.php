<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Models\Area;
use App\Models\StoreOwner;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_franchise_role_matrix_users_and_assignments(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(DemoSeeder::class);

        $franchisee = User::query()->where('email', 'franchisee@fil.test')->firstOrFail();
        $areaRep = User::query()->where('email', 'area_rep@fil.test')->firstOrFail();
        $prospect = User::query()->where('email', 'prospect@fil.test')->firstOrFail();

        $this->assertTrue($franchisee->hasRole('franchisee'));
        $this->assertTrue($areaRep->hasRole('area_rep'));
        $this->assertTrue($prospect->hasRole('prospect'));

        $area = Area::query()->where('slug', 'southwest')->firstOrFail();
        $this->assertSame($areaRep->id, (int) data_get($area->extras, 'rep_user_id'));

        $this->assertTrue(
            StoreOwner::query()
                ->where('user_id', $franchisee->id)
                ->whereHas('store', fn ($query) => $query->where('slug', 'primeiv-scottsdale'))
                ->exists(),
        );

        $this->assertDatabaseCount('leads', 4);
    }
}
