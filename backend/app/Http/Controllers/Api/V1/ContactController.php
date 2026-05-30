<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Contact;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ContactResource;
use App\Models\User;
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
}
