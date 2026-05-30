<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Area;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SyncAreaRepUserIdsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_sync_command_sets_rep_user_id_from_select_reps_extras(): void
    {
        $rep = User::factory()->create(['legacy_user_id' => 9001]);
        $area = Area::factory()->create([
            'extras' => ['select_reps' => (string) $rep->legacy_user_id],
        ]);

        $this->artisan('legacy:sync-area-reps')->assertSuccessful();

        $area->refresh();
        $this->assertSame($rep->id, data_get($area->extras, 'rep_user_id'));
    }
}
