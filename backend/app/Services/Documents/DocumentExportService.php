<?php

declare(strict_types=1);

namespace App\Services\Documents;

use App\Models\Document;
use App\Models\DocumentLink;
use App\Models\FranchiseLocation;
use App\Models\Lead;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

final class DocumentExportService
{
    public function __construct(
        private readonly DocumentLinkQuery $links,
        private readonly DocumentExportRepository $exports,
    ) {}

    /**
     * @return array{export_id: string, status: string}
     */
    public function start(int $userId, string $entity, string $docType, string $docTypeLabel): array
    {
        if ($this->links->linkableTypeForEntity($entity) === null) {
            throw new \InvalidArgumentException('Invalid entity.');
        }

        if ($docType === '') {
            throw new \InvalidArgumentException('Missing doc_type.');
        }

        $exportId = 'exp_'.Str::uuid()->toString();

        $this->exports->put($exportId, [
            'export_id' => $exportId,
            'user_id' => $userId,
            'entity' => $entity,
            'doc_type' => $docType,
            'doc_type_label' => $docTypeLabel,
            'status' => 'preparing',
            'message' => 'Preparing export…',
            'progress' => ['current' => 0, 'total' => 0, 'percentage' => 0],
            'zip_path' => null,
            'error' => null,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        return [
            'export_id' => $exportId,
            'status' => 'processing',
        ];
    }

    public function process(string $exportId): void
    {
        $state = $this->exports->get($exportId);

        if ($state === null) {
            return;
        }

        try {
            $this->update($exportId, [
                'status' => 'processing',
                'message' => 'Collecting documents…',
            ]);

            $entity = (string) $state['entity'];
            $docType = (string) $state['doc_type'];
            $docTypeLabel = (string) ($state['doc_type_label'] ?? $docType);
            $rows = $this->links->allForEntity($entity, $docType);

            if ($rows->isEmpty()) {
                $this->fail($exportId, 'No documents found for this export.');

                return;
            }

            $this->update($exportId, [
                'message' => 'Creating ZIP archive…',
                'progress' => ['current' => 0, 'total' => $rows->count(), 'percentage' => 0],
            ]);

            $zipPath = $this->buildZip($exportId, $entity, $docTypeLabel, $rows);

            $this->update($exportId, [
                'status' => 'completed',
                'message' => 'Export ready.',
                'zip_path' => $zipPath,
                'progress' => [
                    'current' => $rows->count(),
                    'total' => $rows->count(),
                    'percentage' => 100,
                ],
            ]);
        } catch (\Throwable $exception) {
            $this->fail($exportId, $exception->getMessage());
        }
    }

    /**
     * @param  Collection<int, DocumentLink>  $rows
     */
    private function buildZip(string $exportId, string $entity, string $docTypeLabel, $rows): string
    {
        $directory = 'document-exports';
        Storage::disk('local')->makeDirectory($directory);

        $safeLabel = Str::slug($docTypeLabel) ?: 'documents';
        $zipRelative = $directory.'/'.$exportId.'-'.$safeLabel.'.zip';
        $tempZip = tempnam(sys_get_temp_dir(), 'fil-export-');

        if ($tempZip === false) {
            throw new \RuntimeException('Could not create temporary export file.');
        }

        $csvAbsolute = tempnam(sys_get_temp_dir(), 'fil-export-csv-');

        if ($csvAbsolute === false) {
            @unlink($tempZip);
            throw new \RuntimeException('Could not create temporary CSV file.');
        }

        $zip = new ZipArchive;

        if ($zip->open($tempZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($tempZip);
            @unlink($csvAbsolute);
            throw new \RuntimeException('Could not create ZIP file.');
        }

        $csvHandle = fopen($csvAbsolute, 'w');

        if ($csvHandle === false) {
            $zip->close();
            throw new \RuntimeException('Could not create CSV file.');
        }

        fputcsv($csvHandle, $this->csvHeaders($entity));

        $total = $rows->count();
        $current = 0;

        foreach ($rows as $link) {
            $current++;
            $document = $link->document;

            if ($document === null) {
                continue;
            }

            if ($current % 10 === 0 || $current === $total) {
                $this->update($exportId, [
                    'progress' => [
                        'current' => $current,
                        'total' => $total,
                        'percentage' => (int) round(($current / max(1, $total)) * 100),
                    ],
                ]);
            }

            $fileName = $this->resolveFileName($document, $link);
            $added = $this->addDocumentToZip($zip, $document, $fileName);

            fputcsv($csvHandle, $this->csvRow($entity, $link, $fileName, $added));
        }

        fclose($csvHandle);
        $zip->addFile($csvAbsolute, $safeLabel.'.csv');
        $zip->close();

        Storage::disk('local')->put($zipRelative, (string) file_get_contents($tempZip));

        @unlink($tempZip);
        @unlink($csvAbsolute);

        return $zipRelative;
    }

    /**
     * @return list<string>
     */
    private function csvHeaders(string $entity): array
    {
        return match ($entity) {
            'store', 'stores' => ['Spa ID', 'Store', 'File Name', 'Included'],
            'location', 'locations', 'franchise_location' => ['Spa ID', 'Location', 'File Name', 'Included'],
            'user', 'users' => ['User', 'File Name', 'Included'],
            default => ['Entity', 'File Name', 'Included'],
        };
    }

    /**
     * @return list<string|int|bool>
     */
    private function csvRow(string $entity, DocumentLink $link, string $fileName, bool $included): array
    {
        $linkable = $link->linkable;

        return match ($entity) {
            'store', 'stores' => [
                $linkable instanceof Store ? ($linkable->spa_id ?? '') : '',
                $linkable instanceof Store ? $linkable->name : '',
                $fileName,
                $included ? 'yes' : 'no',
            ],
            'location', 'locations', 'franchise_location' => [
                $linkable instanceof FranchiseLocation ? ($linkable->store?->spa_id ?? '') : '',
                $linkable instanceof FranchiseLocation ? $linkable->name : '',
                $fileName,
                $included ? 'yes' : 'no',
            ],
            'user', 'users' => [
                $linkable instanceof User ? ($linkable->name ?? $linkable->email) : '',
                $fileName,
                $included ? 'yes' : 'no',
            ],
            default => [
                match (true) {
                    $linkable instanceof Lead => $linkable->title,
                    $linkable instanceof Store, $linkable instanceof FranchiseLocation => $linkable->name,
                    $linkable instanceof User => $linkable->name ?? $linkable->email,
                    default => '',
                } ?? '',
                $fileName,
                $included ? 'yes' : 'no',
            ],
        };
    }

    private function resolveFileName(Document $document, DocumentLink $link): string
    {
        $base = basename($document->storage_path);

        if ($base !== '' && $base !== '.') {
            return $base;
        }

        return Str::slug($document->title ?: 'document-'.$link->id).'.bin';
    }

    private function addDocumentToZip(ZipArchive $zip, Document $document, string $fileName): bool
    {
        $previewUrl = data_get($document->extras, 'preview_url');

        if (data_get($document->extras, 'url_only') && is_string($previewUrl) && $previewUrl !== '') {
            try {
                $response = Http::timeout(30)->get($previewUrl);

                if ($response->successful()) {
                    $zip->addFromString('files/'.$this->uniqueZipName($fileName, $document->id), $response->body());

                    return true;
                }
            } catch (\Throwable) {
                return false;
            }

            return false;
        }

        if (! Storage::disk($document->storage_disk)->exists($document->storage_path)) {
            return false;
        }

        $contents = Storage::disk($document->storage_disk)->get($document->storage_path);
        $zip->addFromString('files/'.$this->uniqueZipName($fileName, $document->id), $contents);

        return true;
    }

    private function uniqueZipName(string $fileName, int $documentId): string
    {
        return $documentId.'-'.preg_replace('/[^a-zA-Z0-9._-]+/', '_', $fileName);
    }

    private function fail(string $exportId, string $message): void
    {
        $this->update($exportId, [
            'status' => 'error',
            'message' => $message,
            'error' => $message,
        ]);
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function update(string $exportId, array $changes): void
    {
        $state = $this->exports->get($exportId);

        if ($state === null) {
            return;
        }

        $this->exports->put($exportId, [
            ...$state,
            ...$changes,
            'updated_at' => now()->toIso8601String(),
        ]);
    }
}
