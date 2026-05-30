<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class ListFieldsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $entity = $this->query('entity', 'lead');

        return Gate::allows('viewFieldSchemaForEntity', is_string($entity) ? $entity : 'lead');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'entity' => ['sometimes', 'string', Rule::in(['lead', 'store', 'area', 'contact', 'user'])],
        ];
    }

    public function entity(): string
    {
        return (string) $this->validated('entity', 'lead');
    }
}
