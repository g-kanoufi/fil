<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class RoyaltyPeriod extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'royalty_schedule_id',
        'frequency',
        'period_start',
        'period_end',
        'recorded_at',
        'gross_revenue',
        'order_count',
        'total_royalties',
        'status',
        'legacy_period_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_start' => 'datetime',
            'period_end' => 'datetime',
            'recorded_at' => 'datetime',
            'gross_revenue' => 'decimal:2',
            'total_royalties' => 'decimal:2',
            'order_count' => 'integer',
            'legacy_period_id' => 'integer',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(RoyaltyLineItem::class);
    }
}
