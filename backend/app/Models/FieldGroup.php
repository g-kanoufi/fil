<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class FieldGroup extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'title',
        'slug',
        'location_rules',
        'sort_order',
        'status',
        'legacy_group_key',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'location_rules' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function fields(): HasMany
    {
        return $this->hasMany(Field::class)->orderBy('sort_order');
    }
}
