<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleNoteGrant extends Model
{
    protected $fillable = [
        'role',
        'entity',
        'can_view_notes',
        'can_view_private_notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'can_view_notes' => 'boolean',
            'can_view_private_notes' => 'boolean',
        ];
    }
}
