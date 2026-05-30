<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class ListCommunicationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', \App\Models\Communication::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'lead_id' => ['sometimes', 'integer', 'exists:leads,id'],
        ];
    }

    public function leadId(): ?int
    {
        $value = $this->validated('lead_id');

        return $value !== null ? (int) $value : null;
    }
}
