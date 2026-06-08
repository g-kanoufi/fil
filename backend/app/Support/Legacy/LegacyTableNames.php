<?php

declare(strict_types=1);

namespace App\Support\Legacy;

/**
 * Resolves legacy dump table names for multisite dumps (site prefix vs network prefix).
 */
final class LegacyTableNames
{
    public function __construct(
        private readonly string $sitePrefix,
    ) {}

    public static function fromSitePrefix(string $sitePrefix): self
    {
        return new self($sitePrefix);
    }

    public function sitePrefix(): string
    {
        return $this->sitePrefix;
    }

    public function networkPrefix(): string
    {
        if (preg_match('/^(.+?)(?:_\d+_)$/', $this->sitePrefix, $matches)) {
            return $matches[1].'_';
        }

        return $this->sitePrefix;
    }

    public function siteTable(string $suffix): string
    {
        return $this->sitePrefix.$suffix;
    }

    public function networkTable(string $suffix): string
    {
        return $this->networkPrefix().$suffix;
    }
}
