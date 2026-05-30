<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateLeadRequest extends FormRequest
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
            'title' => ['sometimes', 'string', 'max:255'],
            'owner_user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'organization_id' => ['sometimes', 'nullable', 'integer', 'exists:organizations,id'],
            'area_id' => ['sometimes', 'nullable', 'integer', 'exists:areas,id'],
            'lead_status' => ['sometimes', 'nullable', 'string', 'max:64'],
            'lead_stage' => ['sometimes', 'nullable', 'string', 'max:64'],
            'lead_fdd_status' => ['sometimes', 'nullable', 'string', 'max:64'],
            'lead_temp' => ['sometimes', 'nullable', 'string', 'max:32'],
            'lead_source' => ['sometimes', 'nullable', 'string', 'max:64'],
            'likelihood_to_close' => ['sometimes', 'nullable', 'numeric'],
            'eligible_for_drip' => ['sometimes', 'boolean'],
            'form_data' => ['sometimes', 'nullable', 'array'],
            'custom' => ['sometimes', 'array'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function leadAttributes(): array
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
