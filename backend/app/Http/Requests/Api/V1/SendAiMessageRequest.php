<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Models\AiThread;
use Illuminate\Foundation\Http\FormRequest;

final class SendAiMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var AiThread|null $thread */
        $thread = $this->route('aiThread');

        return $thread !== null && ($this->user()?->can('sendMessage', $thread) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:10000'],
        ];
    }
}
