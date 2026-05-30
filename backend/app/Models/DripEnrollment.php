<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class DripEnrollment extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'drip_campaign_id',
        'lead_id',
        'enrolled_at',
        'completed_at',
        'paused_at',
        'status',
        'current_step_id',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enrolled_at' => 'datetime',
            'completed_at' => 'datetime',
            'paused_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(DripCampaign::class, 'drip_campaign_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(DripStep::class, 'current_step_id');
    }

    public function stepRuns(): HasMany
    {
        return $this->hasMany(DripStepRun::class);
    }
}
