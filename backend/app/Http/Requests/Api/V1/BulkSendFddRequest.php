<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class BulkSendFddRequest extends FormRequest
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
            'lead_ids' => ['required', 'array', 'min:1', 'max:200'],
            'lead_ids.*' => ['integer', 'distinct'],
            'type' => ['required', 'string', 'in:unit,area'],
        ];
    }

    /**
     * @return list<int>
     */
    public function leadIds(): array
    {
        return array_map(intval(...), $this->input('lead_ids', []));
    }

    public function fddType(): string
    {
        return (string) $this->input('type');
    }
}
