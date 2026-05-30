<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class StoreWidgetFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manageFields');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_]*$/', 'unique:widget_forms,key'],
            'name' => ['required', 'string', 'max:255'],
            'site_key' => ['sometimes', 'nullable', 'string', 'max:255'],
            'entity' => ['sometimes', 'string', Rule::in(['lead'])],
            'status' => ['sometimes', 'string', Rule::in(['active', 'inactive'])],
            'settings' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
