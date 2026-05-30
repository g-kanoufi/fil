<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Fdd extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'title',
        'slug',
        'area_id',
        'document_id',
        'version',
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
            'version' => 'integer',
            'extras' => 'array',
            'legacy_post_id' => 'integer',
        ];
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(FddDelivery::class);
    }
}
