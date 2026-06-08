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
            'entity' => ['sometimes', 'string', Rule::in(['lead', 'store', 'area', 'contact', 'user', 'organization'])],
            'legacy_post_type' => ['sometimes', 'nullable', 'string', 'max:64'],
        ];
    }

    public function entity(): string
    {
        return (string) $this->validated('entity', 'lead');
    }

    public function legacyPostType(): ?string
    {
        $value = $this->validated('legacy_post_type');

        return is_string($value) && $value !== '' ? $value : null;
    }
}
