<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\Geography\InterestRegionDefaultsSync;
use Illuminate\Database\Seeder;

final class InterestRegionSeeder extends Seeder
{
    public function run(): void
    {
        app(InterestRegionDefaultsSync::class)->sync();
    }
}
