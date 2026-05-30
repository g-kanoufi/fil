<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Lead extends Model
{
    /** @use HasFactory<\Database\Factories\LeadFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'slug',
        'prospect_user_id',
        'owner_user_id',
        'organization_id',
        'area_id',
        'pipeline_phase',
        'lead_status',
        'lead_stage',
        'lead_fdd_status',
        'lead_temp',
        'lead_source',
        'likelihood_to_close',
        'disclosed_at',
        'nda_signed_at',
        'fdd_signed_at',
        'waiting_period_ends_at',
        'drip_campaign_id',
        'eligible_for_drip',
        'status',
        'legacy_post_id',
        'form_data',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pipeline_phase' => 'integer',
            'likelihood_to_close' => 'decimal:2',
            'disclosed_at' => 'datetime',
            'nda_signed_at' => 'datetime',
            'fdd_signed_at' => 'datetime',
            'waiting_period_ends_at' => 'datetime',
        'eligible_for_drip' => 'boolean',
        'legacy_post_id' => 'integer',
        'form_data' => 'array',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prospect_user_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function communications(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Communication::class);
    }

    public function phaseEvents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LeadPhaseEvent::class)->orderByDesc('created_at');
    }

    public function fddDeliveries(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FddDelivery::class)->orderByDesc('sent_at');
    }
}
