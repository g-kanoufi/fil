<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class UpdateDripCampaignRequest extends StoreDripCampaignRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->filled('slug') && $this->filled('name')) {
            $campaign = $this->route('dripCampaign');

            $this->merge([
                'slug' => Str::slug((string) $this->input('name')),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $campaign = $this->route('dripCampaign');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('drip_campaigns', 'slug')->ignore($campaign?->id),
            ],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', Rule::in(['active', 'paused', 'draft'])],
            'trigger_event' => ['sometimes', 'nullable', 'string', 'max:64'],
            'steps' => ['sometimes', 'array'],
            'steps.*.id' => ['sometimes', 'integer', 'exists:drip_steps,id'],
            'steps.*.sort_order' => ['sometimes', 'integer', 'min:1', 'max:999'],
            'steps.*.delay_days' => ['sometimes', 'integer', 'min:0', 'max:3650'],
            'steps.*.delay_hours' => ['sometimes', 'integer', 'min:0', 'max:8760'],
            'steps.*.channel' => ['required_with:steps', Rule::in(['email', 'sms'])],
            'steps.*.subject' => ['nullable', 'string', 'max:500'],
            'steps.*.body_template' => ['required_with:steps', 'string'],
            'steps.*.status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ];
    }
}
