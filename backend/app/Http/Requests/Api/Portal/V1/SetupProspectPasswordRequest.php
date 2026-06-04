<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Portal\V1;

use Illuminate\Foundation\Http\FormRequest;

final class SetupProspectPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'min:32'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
