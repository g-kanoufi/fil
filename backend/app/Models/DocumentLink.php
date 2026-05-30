<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class DocumentLink extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'document_id',
        'linkable_type',
        'linkable_id',
        'role',
        'sort_order',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }
}
