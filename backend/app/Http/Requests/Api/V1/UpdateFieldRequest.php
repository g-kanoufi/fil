<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Support\Fields\FieldTypes;
use App\Support\Fields\RelatableEntities;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class UpdateFieldRequest extends FormRequest
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
            'field_group_id' => ['sometimes', 'integer', 'exists:field_groups,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'string', Rule::in(FieldTypes::all())],
            'required' => ['sometimes', 'boolean'],
            'is_filterable' => ['sometimes', 'boolean'],
            'is_sortable' => ['sometimes', 'boolean'],
            'is_facetable' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'inactive'])],
            'config' => ['sometimes', 'nullable', 'array'],
            'config.choices' => ['sometimes', 'array'],
            'config.related_entity' => ['sometimes', 'string', Rule::in(RelatableEntities::keys())],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('type')) {
                return;
            }

            $type = (string) $this->input('type');

            if (FieldTypes::requiresChoices($type) && empty($this->input('config.choices'))) {
                $validator->errors()->add('config.choices', 'Select fields require at least one choice.');
            }

            if (FieldTypes::isRelation($type) && ! RelatableEntities::isAllowed((string) $this->input('config.related_entity'))) {
                $validator->errors()->add('config.related_entity', 'Relational fields require a valid related entity.');
            }
        });
    }
}
