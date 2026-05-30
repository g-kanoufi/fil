<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UiMenuItem extends Model
{
    protected $fillable = [
        'domain',
        'key',
        'label',
        'parent_key',
        'item_type',
        'sort_order',
        'legacy_adminimize_key',
    ];

    public function grants(): HasMany
    {
        return $this->hasMany(RoleUiGrant::class);
    }
}
