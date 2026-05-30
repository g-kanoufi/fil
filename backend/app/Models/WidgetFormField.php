<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WidgetFormField extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'widget_form_id',
        'field_id',
        'sort_order',
        'label_override',
        'placeholder',
        'required_override',
        'width',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'required_override' => 'boolean',
        ];
    }

    public function widgetForm(): BelongsTo
    {
        return $this->belongsTo(WidgetForm::class);
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class);
    }
}
