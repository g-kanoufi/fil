<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Services\Operations\EntityNoteSubjectResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreStaffTodoRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:10000'],
            'assignee_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'due_at' => ['nullable', 'date'],
            'subject_type' => ['nullable', 'string', Rule::in(app(EntityNoteSubjectResolver::class)->supportedTypes())],
            'subject_id' => ['nullable', 'integer', 'required_with:subject_type'],
        ];
    }
}
