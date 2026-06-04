<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class StoreEntityNoteRequest extends FormRequest
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
            'body' => ['required', 'string', 'max:10000'],
            'is_private' => ['sometimes', 'boolean'],
        ];
    }

    public function bodyText(): string
    {
        return trim($this->string('body')->toString());
    }

    public function isPrivate(): bool
    {
        return $this->boolean('is_private');
    }
}
