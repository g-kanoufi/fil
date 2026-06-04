<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PosSalesSnapshot extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'pos_connection_id',
        'store_id',
        'snapshot_date',
        'period_start',
        'period_end',
        'gross_sales',
        'order_count',
        'raw_payload',
        'synced_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'period_start' => 'datetime',
            'period_end' => 'datetime',
            'gross_sales' => 'decimal:2',
            'order_count' => 'integer',
            'raw_payload' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(PosConnection::class, 'pos_connection_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
