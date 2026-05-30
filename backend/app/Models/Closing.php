<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ClosingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Closing extends Model
{
    /** @use HasFactory<ClosingFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'lead_id',
        'store_id',
        'area_id',
        'closing_date',
        'status',
        'extras',
        'legacy_post_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'closing_date' => 'date',
            'extras' => 'array',
            'legacy_post_id' => 'integer',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
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
