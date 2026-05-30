<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateNotificationRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', \App\Domain\Settings::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'enabled' => ['sometimes', 'boolean'],
            'title' => ['sometimes', 'string', 'max:255'],
            'subject' => ['sometimes', 'nullable', 'string', 'max:500'],
            'body_html' => ['sometimes', 'nullable', 'string'],
            'recipients' => ['sometimes', 'nullable', 'array'],
            'recipients.*' => ['string', 'max:255'],
            'trigger_slug' => ['sometimes', 'string', 'max:255', Rule::in(array_keys(config('fil-notifications.triggers', [])))],
            'conditionals' => ['sometimes', 'nullable', 'array'],
            'conditionals.v' => ['required_with:conditionals', 'integer', Rule::in([2])],
            'conditionals.mode' => ['required_with:conditionals', Rule::in(['always', 'send_if', 'skip_if'])],
            'conditionals.groups' => ['sometimes', 'array'],
            'conditionals.groups.*.match' => ['sometimes', Rule::in(['all', 'any'])],
            'conditionals.groups.*.conditions' => ['sometimes', 'array'],
            'conditionals.groups.*.conditions.*.field' => ['required', 'string', 'max:128'],
            'conditionals.groups.*.conditions.*.op' => ['required', 'string', Rule::in([
                'eq', 'neq', 'empty', 'not_empty', 'contains', 'not_contains',
                'lt', 'gt', 'lte', 'gte', 'regex', 'changed', 'changed_to',
            ])],
            'conditionals.groups.*.conditions.*.value' => ['nullable'],
            'schedule' => ['sometimes', 'nullable', 'array'],
            'schedule.v' => ['required_with:schedule', 'integer', Rule::in([2])],
            'schedule.field' => ['required_with:schedule', 'string', 'max:128'],
            'schedule.direction' => ['required_with:schedule', Rule::in(['on', 'after', 'before'])],
            'schedule.offset_days' => ['sometimes', 'integer', 'min:0', 'max:3650'],
            'schedule.offset_hours' => ['sometimes', 'integer', 'min:0', 'max:8760'],
            'schedule.window_days' => ['sometimes', 'integer', 'min:1', 'max:365'],
            'schedule.send_once' => ['sometimes', 'boolean'],
        ];
    }
}
