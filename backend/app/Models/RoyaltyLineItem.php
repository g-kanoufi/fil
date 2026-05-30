<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RoyaltyLineItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'royalty_period_id',
        'store_id',
        'frequency',
        'trigger_day',
        'royalty_type',
        'royalty_name',
        'gross_revenue',
        'royalty_rate',
        'royalty_amount',
        'ach_source',
        'ach_destination',
        'funding_source',
        'payment_status',
        'ach_transfer_id',
        'external_transfer_id',
        'legacy_line_item_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gross_revenue' => 'decimal:2',
            'royalty_rate' => 'float',
            'royalty_amount' => 'decimal:2',
            'payment_status' => 'integer',
            'legacy_line_item_id' => 'integer',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(RoyaltyPeriod::class, 'royalty_period_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
