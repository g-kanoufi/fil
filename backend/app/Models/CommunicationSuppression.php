<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class CommunicationSuppression extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'channel',
        'address',
        'reason',
        'source',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }
}
