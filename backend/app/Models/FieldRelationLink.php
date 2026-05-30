<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FieldRelationLink extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'field_id',
        'entity_type',
        'entity_id',
        'related_type',
        'related_id',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entity_id' => 'integer',
            'related_id' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class);
    }
}
