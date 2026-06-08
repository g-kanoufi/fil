<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Legacy\LegacyAcfGroupRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class LegacyAcfCatalogCommand extends Command
{
    protected $signature = 'legacy:acf-catalog
                            {path? : Directory of group_*.json (default: FIL_LEGACY_ACF_PATH)}
                            {--output= : Manifest JSON path (default: docs/legacy-acf-site-9-manifest.json)}';

    protected $description = 'Build manifest of legacy ACF groups (post types, import decision).';

    public function handle(LegacyAcfGroupRegistry $registry): int
    {
        $path = $this->argument('path')
            ?? (string) config('fil.legacy.acf_path', base_path('resources/legacy-acf'));

        if (! is_dir($path)) {
            $this->error("ACF path not found: {$path}");

            return self::FAILURE;
        }

        $manifest = [];

        foreach (glob($path.'/group_*.json') ?: [] as $file) {
            $json = json_decode((string) file_get_contents($file), true);

            if (! is_array($json) || ! isset($json['key'])) {
                continue;
            }

            $legacyKey = (string) $json['key'];
            $title = (string) ($json['title'] ?? $legacyKey);
            $location = $json['location'] ?? [];
            /** @var array<string, array<string, mixed>> $configured */
            $configured = config('fil-legacy-acf.groups', []);
            $variants = $configured[$legacyKey]['post_type_variants'] ?? null;

            if (is_array($variants) && $variants !== []) {
                foreach ($variants as $postType => $variant) {
                    if (($variant['import'] ?? true) === false) {
                        continue;
                    }

                    $meta = $registry->resolve($legacyKey, $title, $location, $postType, $variant);

                    $manifest[] = $this->manifestRow(
                        basename($file),
                        $legacyKey,
                        (string) ($variant['title'] ?? $title),
                        $location,
                        $postType,
                        $meta,
                    );
                }

                continue;
            }

            $meta = $registry->resolve($legacyKey, $title, $location);
            $postTypes = $registry->postTypesFromLocation($location);

            $manifest[] = $this->manifestRow(
                basename($file),
                $legacyKey,
                $title,
                $location,
                $postTypes[0] ?? null,
                $meta,
                $postTypes,
            );
        }

        usort($manifest, fn (array $a, array $b) => strcmp((string) $a['title'], (string) $b['title']));

        $output = $this->option('output')
            ?: base_path('../docs/legacy-acf-site-9-manifest.json');

        File::ensureDirectoryExists(dirname((string) $output));
        File::put((string) $output, json_encode([
            'generated_at' => now()->toIso8601String(),
            'source_path' => $path,
            'groups' => $manifest,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

        $imported = count(array_filter($manifest, fn (array $row) => $row['import']));
        $this->info('Wrote '.count($manifest)." groups ({$imported} importable) to {$output}");

        return self::SUCCESS;
    }

    /**
     * @param  list<list<array<string, mixed>>>  $location
     * @param  list<string>|null  $postTypes
     * @param  array{import: bool, key: string, title: string, entity: string, legacy_post_type: string, sort_order: int, merge_into?: string}|null  $meta
     * @return array<string, mixed>
     */
    private function manifestRow(
        string $file,
        string $legacyKey,
        string $title,
        array $location,
        ?string $variantPostType,
        ?array $meta,
        ?array $postTypes = null,
    ): array {
        return [
            'file' => $file,
            'legacy_group_key' => $legacyKey,
            'title' => $title,
            'post_types' => $postTypes ?? ($variantPostType !== null ? [$variantPostType] : []),
            'import' => $meta !== null,
            'fil_group_key' => $meta['key'] ?? null,
            'entity' => $meta['entity'] ?? null,
            'legacy_post_type' => $meta['legacy_post_type'] ?? $variantPostType,
        ];
    }
}
