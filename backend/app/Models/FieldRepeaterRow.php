<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class FieldRepeaterRow extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'entity_type',
        'entity_id',
        'field_id',
        'row_index',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'row_index' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(FieldRepeaterValue::class);
    }
}
