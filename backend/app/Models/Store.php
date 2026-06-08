<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\StoreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Store extends Model
{
    /** @use HasFactory<StoreFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'area_id',
        'status',
        'store_status',
        'buildout_started_at',
        'expected_opening_at',
        'opened_at',
        'next_inspection_at',
        'spa_id',
        'pos_provider',
        'pos_external_id',
        'royalty_config',
        'extras',
        'legacy_post_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'royalty_config' => 'array',
            'extras' => 'array',
            'legacy_post_id' => 'integer',
            'buildout_started_at' => 'date',
            'expected_opening_at' => 'date',
            'opened_at' => 'date',
            'next_inspection_at' => 'date',
        ];
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function storeOwners(): HasMany
    {
        return $this->hasMany(StoreOwner::class);
    }

    public function owners(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'store_owners')
            ->withPivot(['ownership_pct', 'role'])
            ->withTimestamps();
    }

    public function openingChecklistItems(): HasMany
    {
        return $this->hasMany(StoreOpeningChecklistItem::class);
    }

    public function franchiseLocations(): HasMany
    {
        return $this->hasMany(FranchiseLocation::class);
    }
}
