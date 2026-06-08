<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Field extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'field_group_id',
        'parent_field_id',
        'entity',
        'legacy_post_type',
        'key',
        'name',
        'type',
        'storage',
        'maps_to_column',
        'config',
        'sort_order',
        'required',
        'is_filterable',
        'is_sortable',
        'is_facetable',
        'status',
        'legacy_field_key',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'config' => 'array',
            'required' => 'boolean',
            'is_filterable' => 'boolean',
            'is_sortable' => 'boolean',
            'is_facetable' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function fieldGroup(): BelongsTo
    {
        return $this->belongsTo(FieldGroup::class);
    }

    public function parentField(): BelongsTo
    {
        return $this->belongsTo(Field::class, 'parent_field_id');
    }

    public function subFields(): HasMany
    {
        return $this->hasMany(Field::class, 'parent_field_id')->orderBy('sort_order');
    }

    public function roleRules(): HasMany
    {
        return $this->hasMany(FieldRoleRule::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(FieldValue::class);
    }

    public function relationLinks(): HasMany
    {
        return $this->hasMany(FieldRelationLink::class);
    }
}
