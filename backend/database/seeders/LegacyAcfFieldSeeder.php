<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\Legacy\LegacyAcfImportService;
use Illuminate\Database\Seeder;

/**
 * Imports bundled Zorzees ACF JSON into field_groups + fields.
 * Pair with {@see FieldSchemaSeeder} for tier-1 overlays and role rules.
 */
final class LegacyAcfFieldSeeder extends Seeder
{
    public function run(): void
    {
        $path = (string) config('fil.legacy.acf_path');

        if (! is_dir($path)) {
            return;
        }

        app(LegacyAcfImportService::class)->importDirectory($path);
    }
}
