<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class AchCustomer extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'owner_type',
        'owner_id',
        'provider',
        'external_customer_id',
        'status',
        'profile',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'profile' => 'encrypted:array',
        ];
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function fundingSources(): HasMany
    {
        return $this->hasMany(AchFundingSource::class);
    }
}
