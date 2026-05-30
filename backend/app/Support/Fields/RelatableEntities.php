<?php

declare(strict_types=1);

namespace App\Support\Fields;

use App\Models\Area;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Whitelist of entities a relational field may point at. No arbitrary tables.
 */
final class RelatableEntities
{
    /**
     * @var array<string, array{model: class-string<Model>, label: string, label_column: string}>
     */
    private const MAP = [
        'user' => ['model' => User::class, 'label' => 'Users', 'label_column' => 'name'],
        'lead' => ['model' => Lead::class, 'label' => 'Leads', 'label_column' => 'title'],
        'store' => ['model' => Store::class, 'label' => 'Stores', 'label_column' => 'name'],
        'area' => ['model' => Area::class, 'label' => 'Areas', 'label_column' => 'name'],
        'organization' => ['model' => Organization::class, 'label' => 'Organizations', 'label_column' => 'name'],
    ];

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::MAP);
    }

    public static function isAllowed(string $key): bool
    {
        return array_key_exists($key, self::MAP);
    }

    /**
     * @return class-string<Model>|null
     */
    public static function modelFor(string $key): ?string
    {
        return self::MAP[$key]['model'] ?? null;
    }

    public static function labelColumnFor(string $key): ?string
    {
        return self::MAP[$key]['label_column'] ?? null;
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public static function catalogue(): array
    {
        return array_map(
            static fn (string $key): array => ['key' => $key, 'label' => self::MAP[$key]['label']],
            array_keys(self::MAP),
        );
    }
}
