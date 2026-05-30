<?php

declare(strict_types=1);

namespace App\Actions\Fields;

use App\Models\Field;
use Illuminate\Support\Facades\DB;

final class ReorderFields
{
    /**
     * @param  list<array{id: int, sort_order: int, field_group_id?: int}>  $items
     */
    public function handle(array $items): void
    {
        DB::transaction(function () use ($items): void {
            foreach ($items as $item) {
                $attributes = ['sort_order' => $item['sort_order']];

                if (isset($item['field_group_id'])) {
                    $attributes['field_group_id'] = $item['field_group_id'];
                }

                Field::query()->whereKey($item['id'])->update($attributes);
            }
        });
    }
}
