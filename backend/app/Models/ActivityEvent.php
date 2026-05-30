<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ActivityEvent extends Model
{
    protected $fillable = [
        'occurred_at',
        'actor_user_id',
        'actor_name',
        'category',
        'action',
        'summary',
        'subject_type',
        'subject_id',
        'object_type',
        'object_id',
        'source',
        'request_id',
        'payload',
        'legacy_stream_id',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
