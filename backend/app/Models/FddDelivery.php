<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class FddDelivery extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'fdd_id',
        'lead_id',
        'recipient_user_id',
        'sent_at',
        'status',
        'delivery_method',
        'meta',
        'legacy_post_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'meta' => 'array',
            'legacy_post_id' => 'integer',
        ];
    }

    public function fdd(): BelongsTo
    {
        return $this->belongsTo(Fdd::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(Signature::class);
    }

    public function latestSignature(): HasOne
    {
        return $this->hasOne(Signature::class)->latestOfMany();
    }
}
