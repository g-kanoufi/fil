<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('store')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'area_id' => ['sometimes', 'nullable', 'integer', 'exists:areas,id'],
            'store_status' => ['sometimes', 'nullable', 'string', 'max:64'],
            'buildout_started_at' => ['sometimes', 'nullable', 'date'],
            'expected_opening_at' => ['sometimes', 'nullable', 'date'],
            'opened_at' => ['sometimes', 'nullable', 'date'],
            'next_inspection_at' => ['sometimes', 'nullable', 'date'],
            'spa_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'pos_provider' => ['sometimes', 'nullable', 'string', 'max:32'],
            'pos_external_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'custom' => ['sometimes', 'array'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function storeAttributes(): array
    {
        $validated = $this->validated();
        unset($validated['custom']);

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    public function customFieldValues(): array
    {
        $custom = $this->validated('custom');

        return is_array($custom) ? $custom : [];
    }
}
