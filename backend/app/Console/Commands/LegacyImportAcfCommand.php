<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Field;
use App\Models\FieldGroup;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class LegacyImportAcfCommand extends Command
{
    protected $signature = 'legacy:import-acf
                            {path? : Directory containing ACF JSON group files}
                            {--entity=lead : Default entity for imported fields}';

    protected $description = 'Import ACF field group JSON into field_groups and fields tables.';

    public function handle(): int
    {
        $path = $this->argument('path')
            ?? (string) config('fil.legacy.acf_path');

        if (! is_dir($path)) {
            $this->error("ACF path not found: {$path}");

            return self::FAILURE;
        }

        $entity = (string) $this->option('entity');
        $importedGroups = 0;
        $importedFields = 0;

        foreach (glob($path.'/*.json') ?: [] as $file) {
            $json = json_decode((string) file_get_contents($file), true);

            if (! is_array($json) || ! isset($json['key'], $json['title'])) {
                continue;
            }

            $group = FieldGroup::query()->updateOrCreate(
                ['legacy_group_key' => (string) $json['key']],
                [
                    'key' => Str::slug((string) $json['title']),
                    'title' => (string) $json['title'],
                    'slug' => Str::slug((string) $json['title']),
                    'status' => 'active',
                ],
            );

            $importedGroups++;

            foreach ($json['fields'] ?? [] as $index => $acfField) {
                if (! is_array($acfField)) {
                    continue;
                }

                $type = (string) ($acfField['type'] ?? 'text');
                $name = (string) ($acfField['name'] ?? '');

                if ($name === '' || in_array($type, ['tab', 'message', 'accordion'], true)) {
                    continue;
                }

                Field::query()->updateOrCreate(
                    [
                        'field_group_id' => $group->id,
                        'key' => $name,
                    ],
                    [
                        'entity' => $entity,
                        'name' => (string) ($acfField['label'] ?? $name),
                        'type' => $type,
                        'storage' => 'field_value',
                        'config' => [
                            'choices' => $acfField['choices'] ?? null,
                            'legacy_field_key' => $acfField['key'] ?? null,
                        ],
                        'sort_order' => $index + 1,
                        'status' => 'active',
                        'legacy_field_key' => $acfField['key'] ?? null,
                    ],
                );

                $importedFields++;
            }
        }

        $this->info("Imported {$importedGroups} field groups and {$importedFields} fields from {$path}");

        return self::SUCCESS;
    }
}
