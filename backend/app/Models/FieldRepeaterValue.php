<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FieldRepeaterValue extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'field_repeater_row_id',
        'sub_field_id',
        'value_text',
        'value_number',
        'value_boolean',
        'value_date',
        'value_datetime',
        'value_json',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value_number' => 'decimal:6',
            'value_boolean' => 'boolean',
            'value_date' => 'date',
            'value_datetime' => 'datetime',
            'value_json' => 'array',
        ];
    }

    public function row(): BelongsTo
    {
        return $this->belongsTo(FieldRepeaterRow::class, 'field_repeater_row_id');
    }

    public function subField(): BelongsTo
    {
        return $this->belongsTo(Field::class, 'sub_field_id');
    }
}
