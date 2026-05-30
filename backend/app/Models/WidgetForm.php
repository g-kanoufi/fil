<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class WidgetForm extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'name',
        'site_key',
        'entity',
        'version',
        'status',
        'settings',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'settings' => 'array',
        ];
    }

    public function formFields(): HasMany
    {
        return $this->hasMany(WidgetFormField::class)->orderBy('sort_order');
    }
}
