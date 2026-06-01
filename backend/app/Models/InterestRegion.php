<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\InterestRegionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class InterestRegion extends Model
{
    /** @use HasFactory<InterestRegionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'parent_id',
        'name',
        'code',
        'slug',
        'sort_order',
        'status',
        'legacy_term_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
            'sort_order' => 'integer',
            'legacy_term_id' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<InterestRegion, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    /**
     * @return HasMany<Lead, $this>
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function isCountry(): bool
    {
        return $this->parent_id === null;
    }
}
