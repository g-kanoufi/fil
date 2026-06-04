<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Portal\V1;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateProspectApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('accessProspectPortal') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'values' => ['required', 'array'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function fieldValues(): array
    {
        /** @var array<string, mixed> $values */
        $values = $this->input('values', []);

        return $values;
    }
}
