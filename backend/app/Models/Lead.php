<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
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
        'interest_region_id',
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
        'extras',
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
            'extras' => 'array',
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

    public function interestRegion(): BelongsTo
    {
        return $this->belongsTo(InterestRegion::class);
    }

    public function communications(): HasMany
    {
        return $this->hasMany(Communication::class);
    }

    public function phaseEvents(): HasMany
    {
        return $this->hasMany(LeadPhaseEvent::class)->orderByDesc('created_at');
    }

    public function fddDeliveries(): HasMany
    {
        return $this->hasMany(FddDelivery::class)->orderByDesc('sent_at');
    }
}
