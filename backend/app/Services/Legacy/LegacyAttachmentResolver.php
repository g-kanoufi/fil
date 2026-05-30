<?php

declare(strict_types=1);

namespace App\Services\Legacy;

final class LegacyAttachmentResolver
{
    /** @var array<int, array{title: string, mime: string|null, guid: string|null}> */
    private array $attachments = [];

    /** @var array<int, string> */
    private array $files = [];

    public function index(string $dumpPath, string $prefix): void
    {
        $this->attachments = [];
        $this->files = [];

        $tableNeedle = 'INSERT INTO `'.$prefix.'posts`';

        foreach (LegacySqlInsertReader::statements($dumpPath, $tableNeedle) as $statement) {
            $valuesPos = stripos($statement, 'VALUES');

            if ($valuesPos === false) {
                continue;
            }

            foreach (LegacySqlFieldParser::splitTuples(substr($statement, $valuesPos + 6)) as $tuple) {
                $fields = LegacySqlFieldParser::parseFields($tuple);

                if (count($fields) < 21) {
                    continue;
                }

                $postType = $fields[20] ?? '';

                if ($postType !== 'attachment') {
                    continue;
                }

                $id = (int) ($fields[0] ?? 0);

                if ($id <= 0) {
                    continue;
                }

                $this->attachments[$id] = [
                    'title' => (string) ($fields[5] ?? 'Attachment'),
                    'mime' => filled($fields[21] ?? null) ? (string) $fields[21] : null,
                    'guid' => filled($fields[18] ?? null) ? (string) $fields[18] : null,
                ];
            }
        }

        $this->importer()->import(
            $dumpPath,
            $prefix.'postmeta',
            function (array $row, bool $execute): void {
                unset($execute);

                if (($row['meta_key'] ?? '') !== '_wp_attached_file') {
                    return;
                }

                $postId = (int) ($row['post_id'] ?? 0);
                $path = (string) ($row['meta_value'] ?? '');

                if ($postId > 0 && $path !== '') {
                    $this->files[$postId] = $path;
                }
            },
            true,
        );
    }

    /**
     * @return array{title: string, mime: string|null, guid: string|null, relative_path: string|null}|null
     */
    public function resolve(int $attachmentId): ?array
    {
        $meta = $this->attachments[$attachmentId] ?? null;

        if ($meta === null) {
            return null;
        }

        return [
            ...$meta,
            'relative_path' => $this->files[$attachmentId] ?? null,
        ];
    }

    private function importer(): LegacyNamedTableImporter
    {
        return app(LegacyNamedTableImporter::class);
    }
}
