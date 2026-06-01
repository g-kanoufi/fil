<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;

final class StaffCreateLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Lead::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'owner_user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'area_id' => ['sometimes', 'nullable', 'integer', 'exists:areas,id'],
            'interest_region_id' => ['sometimes', 'nullable', 'integer', 'exists:interest_regions,id'],
            'lead_status' => ['sometimes', 'nullable', 'string', 'max:64'],
            'lead_source' => ['sometimes', 'nullable', 'string', 'max:64'],
            'lead_temp' => ['sometimes', 'nullable', 'string', 'max:32'],
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
