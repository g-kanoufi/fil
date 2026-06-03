<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ActivityNavigation extends Model
{
    protected $table = 'activity_navigation';

    protected $fillable = [
        'actor_user_id',
        'actor_name',
        'path_key',
        'period_bucket',
        'subject_type',
        'subject_id',
        'first_seen_at',
        'last_seen_at',
        'view_count',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'period_bucket' => 'date',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'view_count' => 'integer',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
