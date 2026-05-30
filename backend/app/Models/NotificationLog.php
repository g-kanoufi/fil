<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class NotificationLog extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'notification_rule_id',
        'type',
        'component',
        'message',
        'logged_at',
        'meta',
        'legacy_log_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'logged_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(NotificationRule::class, 'notification_rule_id');
    }
}
