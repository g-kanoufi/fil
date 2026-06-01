<?php

declare(strict_types=1);

namespace App\Services\Legacy;

use App\Models\Document;
use App\Models\DocumentLink;
use App\Models\FranchiseLocation;
use App\Models\Lead;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class LegacyDocumentImportService
{
    /** @var array<string, class-string> */
    private const POST_TYPE_ENTITIES = [
        'store' => Store::class,
        'application' => Lead::class,
        'franchise_location' => FranchiseLocation::class,
    ];

    /** @var array<string, array{entity: class-string, role: string, pattern: string}> */
    private const CUSTOM_POSTMETA_PATTERNS = [
        'fdd_receipt' => [
            'entity' => Lead::class,
            'role' => 'fdd_receipt',
            'pattern' => '/^fdd_signed_receipts_(\d+)_fdd_receipt_url$/',
        ],
        'area_fdd_receipt' => [
            'entity' => Lead::class,
            'role' => 'area_fdd_receipt',
            'pattern' => '/^area_fdd_signed_receipts_(\d+)_fdd_receipt_url$/',
        ],
    ];

    /** @var array<string, array{entity: class-string, role: string, pattern: string}> */
    private const CUSTOM_USERMETA_PATTERNS = [
        'medical_certification' => [
            'entity' => User::class,
            'role' => 'medical_certification',
            'pattern' => '/^medical_certification_(\d+)_file$/',
        ],
    ];

    public function __construct(
        private readonly LegacyNamedTableImporter $importer,
        private readonly LegacyAttachmentResolver $attachments,
        private readonly LegacyAcfFilePatternBuilder $acfPatterns,
        private readonly LegacyPostTypeIndex $postTypes,
    ) {}

    /**
     * @return array{documents: int, links: int, skipped: int}
     */
    public function import(string $dumpPath, string $prefix, bool $execute): array
    {
        $this->attachments->index($dumpPath, $prefix);
        $this->postTypes->build($dumpPath, $prefix);

        $patternsByPostType = $this->buildPostTypePatterns();
        $userPatterns = $this->buildUserPatterns();

        $stats = ['documents' => 0, 'links' => 0, 'skipped' => 0];

        $this->importer->import(
            $dumpPath,
            $prefix.'postmeta',
            function (array $row, bool $execute) use (&$stats, $patternsByPostType): void {
                $metaKey = (string) ($row['meta_key'] ?? '');
                $metaValue = trim((string) ($row['meta_value'] ?? ''));
                $legacyPostId = (int) ($row['post_id'] ?? 0);

                if ($legacyPostId <= 0 || $metaValue === '' || str_starts_with($metaKey, '_')) {
                    return;
                }

                foreach (self::CUSTOM_POSTMETA_PATTERNS as $config) {
                    if (! preg_match($config['pattern'], $metaKey, $matches)) {
                        continue;
                    }

                    $sortOrder = (int) ($matches[1] ?? 0);
                    $this->importRow(
                        $config['entity'],
                        $config['role'],
                        $legacyPostId,
                        $metaValue,
                        $sortOrder,
                        $execute,
                        $stats,
                    );

                    return;
                }

                $postType = $this->postTypes->typeFor($legacyPostId);

                if ($postType === null) {
                    return;
                }

                $entityClass = self::POST_TYPE_ENTITIES[$postType] ?? null;
                $patterns = $patternsByPostType[$postType] ?? [];

                if ($entityClass === null || $patterns === []) {
                    return;
                }

                foreach ($patterns as $pattern) {
                    if (! preg_match($pattern['regex'], $metaKey, $matches)) {
                        continue;
                    }

                    $sortOrder = isset($matches[1]) ? (int) $matches[1] : 0;
                    $this->importRow(
                        $entityClass,
                        $pattern['role'],
                        $legacyPostId,
                        $metaValue,
                        $sortOrder,
                        $execute,
                        $stats,
                        label: $pattern['label'],
                    );

                    return;
                }
            },
            $execute,
        );

        $this->importer->import(
            $dumpPath,
            $prefix.'usermeta',
            function (array $row, bool $execute) use (&$stats, $userPatterns): void {
                $metaKey = (string) ($row['meta_key'] ?? '');
                $metaValue = trim((string) ($row['meta_value'] ?? ''));
                $legacyUserId = (int) ($row['user_id'] ?? 0);

                if ($legacyUserId <= 0 || $metaValue === '' || str_starts_with($metaKey, '_')) {
                    return;
                }

                foreach (self::CUSTOM_USERMETA_PATTERNS as $config) {
                    if (! preg_match($config['pattern'], $metaKey, $matches)) {
                        continue;
                    }

                    $sortOrder = (int) ($matches[1] ?? 0);
                    $filUserId = User::query()->where('legacy_user_id', $legacyUserId)->value('id');

                    if ($filUserId === null) {
                        $stats['skipped']++;

                        return;
                    }

                    $this->importRow(
                        User::class,
                        $config['role'],
                        (int) $filUserId,
                        $metaValue,
                        $sortOrder,
                        $execute,
                        $stats,
                        legacyUserId: $legacyUserId,
                    );

                    return;
                }

                foreach ($userPatterns as $pattern) {
                    if (! preg_match($pattern['regex'], $metaKey, $matches)) {
                        continue;
                    }

                    $filUserId = User::query()->where('legacy_user_id', $legacyUserId)->value('id');

                    if ($filUserId === null) {
                        $stats['skipped']++;

                        return;
                    }

                    $sortOrder = isset($matches[1]) ? (int) $matches[1] : 0;
                    $this->importRow(
                        User::class,
                        $pattern['role'],
                        (int) $filUserId,
                        $metaValue,
                        $sortOrder,
                        $execute,
                        $stats,
                        legacyUserId: $legacyUserId,
                        label: $pattern['label'],
                    );

                    return;
                }
            },
            $execute,
        );

        return $stats;
    }

    /**
     * @return array<string, list<array{role: string, label: string, regex: string}>>
     */
    private function buildPostTypePatterns(): array
    {
        $configured = config('fil-documents.acf_field_groups', []);
        $patterns = [];

        foreach ($configured as $postType => $path) {
            $paths = is_array($path) ? $path : (is_string($path) ? [$path] : []);

            foreach ($paths as $relative) {
                if (! is_string($relative)) {
                    continue;
                }

                $resolved = str_starts_with($relative, '/') ? $relative : base_path($relative);
                $patterns[$postType] = array_merge(
                    $patterns[$postType] ?? [],
                    $this->acfPatterns->fromJsonFile($resolved),
                );
            }
        }

        return $patterns;
    }

    /**
     * @return list<array{role: string, label: string, regex: string}>
     */
    private function buildUserPatterns(): array
    {
        $path = config('fil-documents.acf_field_groups.user');

        if (! is_string($path)) {
            return [];
        }

        if (! str_starts_with($path, '/')) {
            $path = base_path($path);
        }

        return $this->acfPatterns->fromJsonFile($path);
    }

    /**
     * @param  class-string  $entityClass
     * @param  array{documents: int, links: int, skipped: int}  $stats
     */
    private function importRow(
        string $entityClass,
        string $role,
        int $legacyOrFilId,
        string $metaValue,
        int $sortOrder,
        bool $execute,
        array &$stats,
        ?int $legacyUserId = null,
        ?string $label = null,
    ): void {
        $linkable = $this->resolveLinkable($entityClass, $legacyOrFilId, $legacyUserId);

        if ($linkable === null) {
            $stats['skipped']++;

            return;
        }

        $stats['documents']++;
        $stats['links']++;

        if (! $execute) {
            return;
        }

        if (is_numeric($metaValue)) {
            $this->importAttachmentDocument(
                $linkable,
                $role,
                (int) $metaValue,
                $sortOrder,
                $legacyOrFilId,
                $entityClass,
                $label,
            );

            return;
        }

        if (filter_var($metaValue, FILTER_VALIDATE_URL)) {
            $this->importUrlDocument(
                $linkable,
                $role,
                $metaValue,
                $sortOrder,
                $legacyOrFilId,
                $entityClass,
                $label,
            );

            return;
        }

        $stats['skipped']--;
        $stats['documents']--;
        $stats['links']--;
    }

    /**
     * @param  class-string  $entityClass
     * @return array{id: int, type: class-string}|null
     */
    private function resolveLinkable(string $entityClass, int $legacyOrFilId, ?int $legacyUserId): ?array
    {
        if ($entityClass === User::class) {
            return ['id' => $legacyOrFilId, 'type' => User::class];
        }

        $id = $entityClass::query()->where('legacy_post_id', $legacyOrFilId)->value('id');

        return $id !== null ? ['id' => (int) $id, 'type' => $entityClass] : null;
    }

    /**
     * @param  array{id: int, type: class-string}  $linkable
     * @param  class-string  $entityClass
     */
    private function importAttachmentDocument(
        array $linkable,
        string $role,
        int $attachmentId,
        int $sortOrder,
        int $legacySourceId,
        string $entityClass,
        ?string $label = null,
    ): void {
        $attachment = $this->attachments->resolve($attachmentId);

        if ($attachment === null) {
            return;
        }

        $relativePath = $attachment['relative_path'] ?? 'legacy/'.$attachmentId;
        $storagePath = 'legacy/'.$attachmentId.'/'.basename($relativePath);

        if (
            filled(config('fil-documents.legacy_uploads_path'))
            && filled($attachment['relative_path'])
            && ! Storage::disk('local')->exists($storagePath)
        ) {
            $source = rtrim((string) config('fil-documents.legacy_uploads_path'), '/')
                .'/'.ltrim($attachment['relative_path'], '/');

            if (is_readable($source)) {
                Storage::disk('local')->put($storagePath, (string) file_get_contents($source));
            }
        }

        $document = Document::query()->updateOrCreate(
            ['legacy_post_id' => $attachmentId],
            [
                'title' => $label ?? $attachment['title'],
                'mime_type' => $attachment['mime'],
                'storage_disk' => 'local',
                'storage_path' => $storagePath,
                'status' => 'active',
                'extras' => [
                    'legacy_field_role' => $role,
                    'legacy_field_label' => $label,
                    'legacy_source_entity' => class_basename($entityClass),
                    'legacy_source_id' => $legacySourceId,
                    'legacy_relative_path' => $attachment['relative_path'],
                    'legacy_guid' => $attachment['guid'],
                ],
            ],
        );

        DocumentLink::query()->updateOrCreate(
            [
                'document_id' => $document->id,
                'linkable_type' => $linkable['type'],
                'linkable_id' => $linkable['id'],
                'role' => $role,
                'sort_order' => $sortOrder,
            ],
            [],
        );
    }

    /**
     * @param  array{id: int, type: class-string}  $linkable
     * @param  class-string  $entityClass
     */
    private function importUrlDocument(
        array $linkable,
        string $role,
        string $url,
        int $sortOrder,
        int $legacySourceId,
        string $entityClass,
        ?string $label = null,
    ): void {
        $hash = sha1($role.'|'.$legacySourceId.'|'.$sortOrder.'|'.$url);

        $document = Document::query()->updateOrCreate(
            ['slug' => 'legacy-url-'.Str::limit($hash, 32, '')],
            [
                'title' => $label ?? (config('fil-documents.labels.'.$role, $role).' #'.($sortOrder + 1)),
                'mime_type' => 'application/pdf',
                'storage_disk' => 'local',
                'storage_path' => 'legacy/urls/'.$hash.'.url',
                'status' => 'active',
                'extras' => [
                    'preview_url' => $url,
                    'legacy_field_role' => $role,
                    'legacy_field_label' => $label,
                    'legacy_source_entity' => class_basename($entityClass),
                    'legacy_source_id' => $legacySourceId,
                    'url_only' => true,
                ],
            ],
        );

        if (! Storage::disk('local')->exists($document->storage_path)) {
            Storage::disk('local')->put($document->storage_path, $url);
        }

        DocumentLink::query()->updateOrCreate(
            [
                'document_id' => $document->id,
                'linkable_type' => $linkable['type'],
                'linkable_id' => $linkable['id'],
                'role' => $role,
                'sort_order' => $sortOrder,
            ],
            [],
        );
    }
}
