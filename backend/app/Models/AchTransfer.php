<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AchTransfer extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'royalty_period_id',
        'area_id',
        'transferred_at',
        'source_funding_source_id',
        'destination_funding_source_id',
        'external_transfer_id',
        'provider',
        'provider_status',
        'status',
        'amount',
        'royalty_name',
        'description',
        'addenda',
        'correlation_id',
        'errors',
        'meta',
        'legacy_transfer_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'transferred_at' => 'datetime',
            'amount' => 'decimal:2',
            'status' => 'integer',
            'meta' => 'array',
            'legacy_transfer_id' => 'integer',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }
}
