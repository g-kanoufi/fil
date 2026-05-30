<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AreaRoyalty extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'area_id',
        'period',
        'frequency',
        'recorded_at',
        'store_ids',
        'royalty_line_item_ids',
        'sum_total_sales',
        'sum_unit_royalties',
        'sum_area_royalties',
        'sum_ach_available',
        'percentage',
        'amount',
        'payment_status',
        'ach_destination',
        'ach_transfer_id',
        'external_transfer_id',
        'trigger_day',
        'errors',
        'royalty_detail',
        'legacy_area_royalty_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period' => 'date',
            'recorded_at' => 'datetime',
            'store_ids' => 'array',
            'royalty_line_item_ids' => 'array',
            'sum_total_sales' => 'decimal:2',
            'sum_unit_royalties' => 'decimal:2',
            'sum_area_royalties' => 'decimal:2',
            'sum_ach_available' => 'decimal:2',
            'percentage' => 'float',
            'amount' => 'decimal:2',
            'payment_status' => 'integer',
            'royalty_detail' => 'array',
            'legacy_area_royalty_id' => 'integer',
        ];
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }
}
