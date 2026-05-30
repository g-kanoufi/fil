<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class GridQueryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'filters' => ['sometimes', 'array'],
            'sort' => ['sometimes', 'array'],
            'cursor' => ['sometimes', 'nullable', 'string'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:200'],
            'size' => ['sometimes', 'integer'],
            'include_aggregations' => ['sometimes', 'boolean'],
            'aggregations' => ['sometimes', 'array'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function queryPayload(): array
    {
        return $this->validated();
    }
}
