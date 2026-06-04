<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AreaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Area extends Model
{
    /** @use HasFactory<AreaFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'status',
        'approval_status',
        'territory',
        'extras',
        'legacy_post_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'territory' => 'array',
            'extras' => 'array',
            'legacy_post_id' => 'integer',
        ];
    }

    /**
     * @return HasMany<Store, $this>
     */
    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }
}
