<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\InterestRegion;
use App\Support\Geography\InterestRegionCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class InterestRegionSeeder extends Seeder
{
    public function run(): void
    {
        $sortOrder = 0;

        foreach (InterestRegionCatalog::countries() as $country) {
            $sortOrder++;
            $countryModel = InterestRegion::query()->updateOrCreate(
                ['parent_id' => null, 'slug' => Str::slug($country['name'])],
                [
                    'name' => $country['name'],
                    'code' => $country['code'],
                    'sort_order' => $sortOrder,
                    'status' => 'active',
                ],
            );

            $childSort = 0;

            foreach ($country['subdivisions'] as $subdivision) {
                $childSort++;
                InterestRegion::query()->updateOrCreate(
                    [
                        'parent_id' => $countryModel->id,
                        'slug' => Str::slug($subdivision['name']),
                    ],
                    [
                        'name' => $subdivision['name'],
                        'code' => $subdivision['code'],
                        'sort_order' => $childSort,
                        'status' => 'active',
                    ],
                );
            }
        }
    }
}
