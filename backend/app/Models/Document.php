<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Document extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'slug',
        'mime_type',
        'storage_disk',
        'storage_path',
        'file_size',
        'checksum',
        'version',
        'status',
        'uploaded_by_user_id',
        'extras',
        'legacy_post_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'extras' => 'array',
            'legacy_post_id' => 'integer',
        ];
    }

    public function links(): HasMany
    {
        return $this->hasMany(DocumentLink::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(Signature::class);
    }
}
