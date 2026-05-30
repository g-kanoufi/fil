<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Models\AiThread;
use Illuminate\Foundation\Http\FormRequest;

final class CreateAiThreadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', AiThread::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'lead_id' => ['sometimes', 'nullable', 'integer', 'exists:leads,id'],
        ];
    }
}
