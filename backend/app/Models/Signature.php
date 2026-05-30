<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Signature extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'document_id',
        'lead_id',
        'fdd_delivery_id',
        'signer_user_id',
        'signed_at',
        'status',
        'signature_data',
        'ip_address',
        'extras',
        'legacy_post_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
            'signature_data' => 'array',
            'extras' => 'array',
            'legacy_post_id' => 'integer',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function fddDelivery(): BelongsTo
    {
        return $this->belongsTo(FddDelivery::class);
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signer_user_id');
    }
}
