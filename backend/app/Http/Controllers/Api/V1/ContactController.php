<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateContactRequest;
use App\Http\Resources\Api\V1\ContactResource;
use App\Models\User;
use App\Services\Activity\ActivityRecorder;
use App\Services\Fields\FieldValueWriter;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

final class ContactController extends Controller
{
    public function show(User $contact): JsonResponse
    {
        $this->authorize('viewContact', $contact);

        $contact->load('roles');

        return ApiResponse::resource(new ContactResource($contact));
    }

    public function update(
        UpdateContactRequest $request,
        User $contact,
        FieldValueWriter $fieldWriter,
        ActivityRecorder $activity,
    ): JsonResponse {
        if ($request->has('custom')) {
            $fieldWriter->write('contact', $contact->id, $request->customFieldValues());
        }

        $contact->load('roles');

        if ($request->has('custom')) {
            $actor = $request->user();
            $activity->record(
                category: 'contact',
                action: 'updated',
                summary: sprintf(
                    '%s updated contact "%s" (custom fields)',
                    $actor?->name ?? 'Staff',
                    $contact->name,
                ),
                actor: $actor,
                subject: $contact,
                payload: ['changed_keys' => ['custom']],
            );
        }

        return ApiResponse::resource(new ContactResource($contact));
    }
}
