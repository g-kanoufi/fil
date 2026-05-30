<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class DripCampaign extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
        'trigger_event',
        'extras',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'extras' => 'array',
        ];
    }

    public function steps(): HasMany
    {
        return $this->hasMany(DripStep::class)->orderBy('sort_order');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(DripEnrollment::class);
    }
}
