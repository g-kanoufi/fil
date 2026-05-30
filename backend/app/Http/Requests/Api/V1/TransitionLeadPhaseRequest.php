<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TransitionLeadPhaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('lead')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'to_phase' => ['required', 'integer', Rule::in([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 99])],
            'meta' => ['sometimes', 'array'],
        ];
    }

    public function toPhase(): int
    {
        return (int) $this->validated('to_phase');
    }

    /**
     * @return array<string, mixed>
     */
    public function meta(): array
    {
        return (array) $this->validated('meta', []);
    }
}
