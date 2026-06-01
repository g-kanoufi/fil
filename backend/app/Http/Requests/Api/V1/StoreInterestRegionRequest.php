<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Models\InterestRegion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreInterestRegionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', InterestRegion::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $parentId = $this->input('parent_id');

        return [
            'parent_id' => ['nullable', 'integer', 'exists:interest_regions,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:8'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('interest_regions', 'slug')->where(
                    fn ($query) => $query->where('parent_id', $parentId),
                ),
            ],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'inactive'])],
            'legacy_term_id' => ['nullable', 'integer', 'unique:interest_regions,legacy_term_id'],
        ];
    }
}
