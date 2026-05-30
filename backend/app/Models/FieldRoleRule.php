<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FieldRoleRule extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'field_id',
        'role',
        'permission',
        'conditions',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'conditions' => 'array',
        ];
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class);
    }
}
