<?php

declare(strict_types=1);

namespace App\Services\Legacy;

use App\Models\Area;
use App\Models\Closing;
use App\Models\Fdd;
use App\Models\FranchiseLocation;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\Store;

final class LegacyPostImportService
{
    /**
     * @param  list<string>  $only
     * @return array{leads: int, stores: int, areas: int, organizations: int, franchise_locations: int, fdds: int, closings: int, skipped: int}
     */
    public function import(string $dumpPath, string $prefix, array $only, bool $execute): array
    {
        $stats = [
            'leads' => 0,
            'stores' => 0,
            'areas' => 0,
            'organizations' => 0,
            'franchise_locations' => 0,
            'fdds' => 0,
            'closings' => 0,
            'skipped' => 0,
        ];
        $tableNeedle = 'INSERT INTO `'.$prefix.'posts`';

        foreach (LegacySqlInsertReader::statements($dumpPath, $tableNeedle) as $statement) {
            $valuesPos = stripos($statement, 'VALUES');

            if ($valuesPos === false) {
                continue;
            }

            $valuesSection = substr($statement, $valuesPos + 6);

            foreach (LegacySqlFieldParser::splitTuples($valuesSection) as $tuple) {
                $fields = LegacySqlFieldParser::parseFields($tuple);

                if (count($fields) < 21) {
                    $stats['skipped']++;

                    continue;
                }

                $legacyPostId = (int) ($fields[0] ?? 0);
                $title = (string) ($fields[5] ?? 'Untitled');
                $slug = (string) ($fields[11] ?? '');
                $postType = (string) ($fields[20] ?? '');

                if ($legacyPostId <= 0) {
                    $stats['skipped']++;

                    continue;
                }

                match ($postType) {
                    'application' => $this->importLead($legacyPostId, $title, $slug, $only, $execute, $stats),
                    'store' => $this->importStore($legacyPostId, $title, $slug, $only, $execute, $stats),
                    'area' => $this->importArea($legacyPostId, $title, $slug, $only, $execute, $stats),
                    'organization' => $this->importOrganization($legacyPostId, $title, $slug, $only, $execute, $stats),
                    'franchise_location' => $this->importFranchiseLocation($legacyPostId, $title, $slug, $only, $execute, $stats),
                    'grabbafdd', 'areafdd' => $this->importFdd($legacyPostId, $title, $slug, $postType, $only, $execute, $stats),
                    'closing' => $this->importClosing($legacyPostId, $title, $only, $execute, $stats),
                    default => $stats['skipped']++,
                };
            }
        }

        return $stats;
    }

    /**
     * @param  list<string>  $only
     * @param  array{leads: int, stores: int, areas: int, organizations: int, fdds: int, closings: int, skipped: int}  $stats
     */
    private function importLead(
        int $legacyPostId,
        string $title,
        string $slug,
        array $only,
        bool $execute,
        array &$stats,
    ): void {
        if (! in_array('leads', $only, true)) {
            return;
        }

        $stats['leads']++;

        if (! $execute) {
            return;
        }

        Lead::query()->updateOrCreate(
            ['legacy_post_id' => $legacyPostId],
            [
                'title' => $title,
                'slug' => $slug !== '' ? $slug : null,
                'pipeline_phase' => 1,
                'status' => 'active',
            ],
        );
    }

    /**
     * @param  list<string>  $only
     * @param  array{leads: int, stores: int, areas: int, organizations: int, fdds: int, closings: int, skipped: int}  $stats
     */
    private function importStore(
        int $legacyPostId,
        string $title,
        string $slug,
        array $only,
        bool $execute,
        array &$stats,
    ): void {
        if (! in_array('stores', $only, true)) {
            return;
        }

        $stats['stores']++;

        if (! $execute) {
            return;
        }

        Store::query()->updateOrCreate(
            ['legacy_post_id' => $legacyPostId],
            [
                'name' => $title,
                'slug' => $slug !== '' ? $slug : null,
                'status' => 'active',
            ],
        );
    }

    /**
     * @param  list<string>  $only
     * @param  array{leads: int, stores: int, areas: int, organizations: int, fdds: int, closings: int, skipped: int}  $stats
     */
    private function importArea(
        int $legacyPostId,
        string $title,
        string $slug,
        array $only,
        bool $execute,
        array &$stats,
    ): void {
        if (! in_array('areas', $only, true)) {
            return;
        }

        $stats['areas']++;

        if (! $execute) {
            return;
        }

        Area::query()->updateOrCreate(
            ['legacy_post_id' => $legacyPostId],
            [
                'name' => $title,
                'slug' => $slug !== '' ? $slug : null,
                'status' => 'active',
            ],
        );
    }

    /**
     * @param  list<string>  $only
     * @param  array{leads: int, stores: int, areas: int, organizations: int, franchise_locations: int, fdds: int, closings: int, skipped: int}  $stats
     */
    private function importFranchiseLocation(
        int $legacyPostId,
        string $title,
        string $slug,
        array $only,
        bool $execute,
        array &$stats,
    ): void {
        if (! in_array('franchise_locations', $only, true)) {
            return;
        }

        $stats['franchise_locations']++;

        if (! $execute) {
            return;
        }

        FranchiseLocation::query()->updateOrCreate(
            ['legacy_post_id' => $legacyPostId],
            [
                'name' => $title,
                'slug' => $slug !== '' ? $slug : null,
                'status' => 'active',
            ],
        );
    }

    /**
     * @param  list<string>  $only
     * @param  array{leads: int, stores: int, areas: int, organizations: int, franchise_locations: int, fdds: int, closings: int, skipped: int}  $stats
     */
    private function importOrganization(
        int $legacyPostId,
        string $title,
        string $slug,
        array $only,
        bool $execute,
        array &$stats,
    ): void {
        if (! in_array('organizations', $only, true)) {
            return;
        }

        $stats['organizations']++;

        if (! $execute) {
            return;
        }

        Organization::query()->updateOrCreate(
            ['legacy_post_id' => $legacyPostId],
            [
                'name' => $title,
                'slug' => $slug !== '' ? $slug : null,
                'status' => 'active',
            ],
        );
    }

    /**
     * @param  list<string>  $only
     * @param  array{leads: int, stores: int, areas: int, organizations: int, fdds: int, closings: int, skipped: int}  $stats
     */
    private function importFdd(
        int $legacyPostId,
        string $title,
        string $slug,
        string $postType,
        array $only,
        bool $execute,
        array &$stats,
    ): void {
        if (! in_array('fdds', $only, true)) {
            return;
        }

        $stats['fdds']++;

        if (! $execute) {
            return;
        }

        Fdd::query()->updateOrCreate(
            ['legacy_post_id' => $legacyPostId],
            [
                'type' => $postType === 'areafdd' ? 'area' : 'unit',
                'title' => $title,
                'slug' => $slug !== '' ? $slug : null,
                'status' => 'active',
            ],
        );
    }

    /**
     * @param  list<string>  $only
     * @param  array{leads: int, stores: int, areas: int, organizations: int, fdds: int, closings: int, skipped: int}  $stats
     */
    private function importClosing(
        int $legacyPostId,
        string $title,
        array $only,
        bool $execute,
        array &$stats,
    ): void {
        if (! in_array('closings', $only, true)) {
            return;
        }

        $stats['closings']++;

        if (! $execute) {
            return;
        }

        Closing::query()->updateOrCreate(
            ['legacy_post_id' => $legacyPostId],
            [
                'title' => $title,
                'status' => 'pending',
            ],
        );
    }
}
