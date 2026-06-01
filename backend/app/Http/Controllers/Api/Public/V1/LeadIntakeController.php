<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Public\V1;

use App\Actions\Leads\CreatePublicLead;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Public\V1\StoreLeadRequest;
use App\Http\Resources\Api\Public\V1\PublicLeadResource;
use App\Models\Field;
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
            $fieldsByKey = Field::query()
                ->whereIn('key', array_keys($custom))
                ->where('status', 'active')
                ->get()
                ->keyBy('key');

            $leadValues = [];
            $contactValues = [];

            foreach ($custom as $key => $value) {
                $field = $fieldsByKey->get($key);

                if ($field === null) {
                    continue;
                }

                if ($field->entity === 'contact') {
                    $contactValues[$key] = $value;
                } else {
                    $leadValues[$key] = $value;
                }
            }

            if ($leadValues !== []) {
                $fieldValues->write('lead', $lead->id, $leadValues);
            }

            if ($contactValues !== [] && $lead->prospect_user_id !== null) {
                $fieldValues->write('contact', (int) $lead->prospect_user_id, $contactValues);
            }
        }

        return ApiResponse::resource(new PublicLeadResource($lead), 201);
    }
}
