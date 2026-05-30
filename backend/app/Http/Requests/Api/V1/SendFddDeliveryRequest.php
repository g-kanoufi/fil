<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class SendFddDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('send', $this->route('fdd')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'recipient_user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
        ];
    }
}
