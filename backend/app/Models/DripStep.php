<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class DripStep extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'drip_campaign_id',
        'sort_order',
        'delay_days',
        'delay_hours',
        'channel',
        'subject',
        'body_template',
        'template_id',
        'conditions',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'delay_days' => 'integer',
            'delay_hours' => 'integer',
            'conditions' => 'array',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(DripCampaign::class, 'drip_campaign_id');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(DripStepRun::class);
    }
}
