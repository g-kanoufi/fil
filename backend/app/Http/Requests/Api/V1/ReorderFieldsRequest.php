<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class ReorderFieldsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manageFields');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:fields,id'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
            'items.*.field_group_id' => ['sometimes', 'integer', 'exists:field_groups,id'],
        ];
    }

    /**
     * @return list<array{id: int, sort_order: int, field_group_id?: int}>
     */
    public function items(): array
    {
        /** @var list<array<string, mixed>> $items */
        $items = $this->validated('items', []);

        return array_map(static function (array $item): array {
            $mapped = [
                'id' => (int) $item['id'],
                'sort_order' => (int) $item['sort_order'],
            ];

            if (isset($item['field_group_id'])) {
                $mapped['field_group_id'] = (int) $item['field_group_id'];
            }

            return $mapped;
        }, $items);
    }
}
