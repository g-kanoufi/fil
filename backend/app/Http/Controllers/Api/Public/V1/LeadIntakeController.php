<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Public\V1;

use App\Actions\Leads\CreatePublicLead;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Public\V1\StoreLeadRequest;
use App\Http\Resources\Api\Public\V1\PublicLeadResource;
use App\Services\Fields\FieldValueWriter;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

final class LeadIntakeController extends Controller
{
    public function store(
        StoreLeadRequest $request,
        CreatePublicLead $createPublicLead,
        FieldValueWriter $fieldValues,
    ): JsonResponse {
        $lead = $createPublicLead->handle($request->validated());

        $custom = $request->customValues();

        if ($custom !== []) {
            $fieldValues->write('lead', $lead->id, $custom);
        }

        return ApiResponse::resource(new PublicLeadResource($lead), 201);
    }
}
