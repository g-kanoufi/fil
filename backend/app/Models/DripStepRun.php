<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DripStepRun extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'drip_enrollment_id',
        'drip_step_id',
        'scheduled_at',
        'sent_at',
        'status',
        'communication_id',
        'error',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(DripEnrollment::class, 'drip_enrollment_id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(DripStep::class, 'drip_step_id');
    }

    public function communication(): BelongsTo
    {
        return $this->belongsTo(Communication::class);
    }
}
