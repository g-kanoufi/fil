<?php

declare(strict_types=1);

namespace App\Services\Legacy;

final class LegacyExtrasResolvedKey
{
    private function __construct(
        public readonly string $kind,
        public readonly ?string $fieldKey = null,
        public readonly ?int $rowIndex = null,
        public readonly ?string $subKey = null,
        public readonly ?string $nestedKey = null,
        public readonly ?string $metaKey = null,
    ) {}

    public static function discard(): self
    {
        return new self('discard');
    }

    public static function scalar(string $fieldKey): self
    {
        return new self('scalar', $fieldKey);
    }

    public static function repeaterRow(string $fieldKey, int $rowIndex, string $subKey): self
    {
        return new self('repeater_row', $fieldKey, $rowIndex, $subKey);
    }

    public static function nestedRepeaterRow(string $fieldKey, string $nestedKey, int $rowIndex, string $subKey): self
    {
        return new self('nested_repeater_row', $fieldKey, $rowIndex, $subKey, $nestedKey);
    }

    public static function relationColumn(string $metaKey): self
    {
        return new self('relation_column', metaKey: $metaKey);
    }

    public function isDiscard(): bool
    {
        return $this->kind === 'discard';
    }

    public function isScalar(): bool
    {
        return $this->kind === 'scalar';
    }

    public function isRepeaterRow(): bool
    {
        return $this->kind === 'repeater_row';
    }

    public function isNestedRepeaterRow(): bool
    {
        return $this->kind === 'nested_repeater_row';
    }

    public function isRelationColumn(): bool
    {
        return $this->kind === 'relation_column';
    }
}
