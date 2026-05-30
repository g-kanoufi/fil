<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreDripCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', \App\Domain\Settings::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('slug') && $this->filled('name')) {
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
        return array_merge($this->campaignRules(), $this->stepRules(prefix: 'steps'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function campaignRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('drip_campaigns', 'slug')],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(['active', 'paused', 'draft'])],
            'trigger_event' => ['sometimes', 'nullable', 'string', 'max:64'],
            'steps' => ['sometimes', 'array'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function stepRules(string $prefix): array
    {
        return [
            "{$prefix}" => ['sometimes', 'array'],
            "{$prefix}.*.id" => ['sometimes', 'integer', 'exists:drip_steps,id'],
            "{$prefix}.*.sort_order" => ['sometimes', 'integer', 'min:1', 'max:999'],
            "{$prefix}.*.delay_days" => ['sometimes', 'integer', 'min:0', 'max:3650'],
            "{$prefix}.*.delay_hours" => ['sometimes', 'integer', 'min:0', 'max:8760'],
            "{$prefix}.*.channel" => ['required_with:'.$prefix, Rule::in(['email', 'sms'])],
            "{$prefix}.*.subject" => ['nullable', 'string', 'max:500'],
            "{$prefix}.*.body_template" => ['required_with:'.$prefix, 'string'],
            "{$prefix}.*.status" => ['sometimes', Rule::in(['active', 'inactive'])],
        ];
    }
}
