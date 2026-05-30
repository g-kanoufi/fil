<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AchFundingSource extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'ach_customer_id',
        'external_funding_source_id',
        'name',
        'type',
        'status',
        'is_default',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(AchCustomer::class, 'ach_customer_id');
    }
}
