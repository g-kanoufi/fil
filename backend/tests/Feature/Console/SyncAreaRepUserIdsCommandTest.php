<?php

declare(strict_types=1);
use App\Models\Area;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});
test('sync command sets rep user id from select reps extras', function () {
    $rep = User::factory()->create(['legacy_user_id' => 9001]);
    $area = Area::factory()->create([
        'extras' => ['select_reps' => (string) $rep->legacy_user_id],
    ]);

    $this->artisan('legacy:sync-area-reps')->assertSuccessful();

    $area->refresh();
    expect(data_get($area->extras, 'rep_user_id'))->toBe($rep->id);
});
