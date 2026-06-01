<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Models\InterestRegion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateInterestRegionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var InterestRegion|null $region */
        $region = $this->route('interestRegion');

        return $region !== null && ($this->user()?->can('update', $region) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var InterestRegion $region */
        $region = $this->route('interestRegion');
        $parentId = $this->input('parent_id', $region->parent_id);

        return [
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:interest_regions,id', 'not_in:'.$region->id],
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'nullable', 'string', 'max:8'],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('interest_regions', 'slug')
                    ->where(fn ($query) => $query->where('parent_id', $parentId))
                    ->ignore($region->id),
            ],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'inactive'])],
            'legacy_term_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::unique('interest_regions', 'legacy_term_id')->ignore($region->id),
            ],
        ];
    }
}
