<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Support\Widget\WidgetFieldCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class SyncWidgetFormFieldsRequest extends FormRequest
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
            'fields' => ['present', 'array'],
            'fields.*.field_id' => [
                'required',
                'integer',
                Rule::exists('fields', 'id')->where(function ($query): void {
                    $query->where('entity', 'lead')
                        ->whereIn('field_group_id', WidgetFieldCatalog::allowedGroupIds());
                }),
            ],
            'fields.*.sort_order' => ['sometimes', 'integer', 'min:0'],
            'fields.*.label_override' => ['sometimes', 'nullable', 'string', 'max:255'],
            'fields.*.placeholder' => ['sometimes', 'nullable', 'string', 'max:255'],
            'fields.*.required_override' => ['sometimes', 'nullable', 'boolean'],
            'fields.*.width' => ['sometimes', 'nullable', 'string', Rule::in(['full', 'half'])],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function fieldsPayload(): array
    {
        /** @var list<array<string, mixed>> $fields */
        $fields = $this->validated('fields', []);

        return $fields;
    }
}
