<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class PageViewIngestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('app.access') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $max = (int) config('fil-activity.navigation.max_paths_per_request', 5);

        return [
            'paths' => ['required', 'array', 'min:1', "max:{$max}"],
            'paths.*' => ['required', 'string', 'max:512'],
        ];
    }

    /**
     * @return list<string>
     */
    public function paths(): array
    {
        /** @var list<string> $paths */
        $paths = $this->validated('paths');

        return $paths;
    }
}
