<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class GridQueryInterpretRequest extends FormRequest
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
            'q' => ['required', 'string', 'min:2', 'max:500'],
            'resource' => ['sometimes', Rule::in(['leads', 'stores', 'contacts'])],
        ];
    }

    public function queryText(): string
    {
        return trim((string) $this->validated('q'));
    }
}
