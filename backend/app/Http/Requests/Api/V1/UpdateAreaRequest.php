<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Models\Area;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $area = $this->route('area');

        return $area instanceof Area && ($this->user()?->can('update', $area) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Area $area */
        $area = $this->route('area');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('areas', 'slug')->ignore($area->id)],
            'status' => ['sometimes', 'string', Rule::in(['active', 'inactive'])],
            'approval_status' => ['nullable', 'string', 'max:32'],
            'territory' => ['nullable', 'array'],
            'territory.country' => ['nullable', 'string', 'max:8'],
            'territory.subdivisions' => ['nullable', 'array'],
            'territory.subdivisions.*' => ['string', 'max:8'],
        ];
    }
}
