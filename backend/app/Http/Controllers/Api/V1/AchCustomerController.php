<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Ach\DwollaClient;
use App\Contracts\Ach\PlaidClient;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DwollaClientTokenRequest;
use App\Models\AchCustomer;
use App\Models\Store;
use App\Models\User;
use App\Services\Ach\AchDwollaEnrollmentService;
use App\Services\Ach\AchDwollaEnrollmentSession;
use App\Services\Ach\AchPlaidLinkService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

final class AchCustomerController extends Controller
{
    public function show(
        Store $store,
        PlaidClient $plaid,
        AchDwollaEnrollmentService $dwollaEnrollment,
    ): JsonResponse {
        $this->authorize('view', $store);
        $this->authorize('viewAny', AchCustomer::class);

        $plaidMode = $plaid->isConfigured() ? 'live' : 'sandbox';

        $customer = AchCustomer::query()
            ->where('owner_type', Store::class)
            ->where('owner_id', $store->id)
            ->with('fundingSources')
            ->first();

        $dwollaConfig = $dwollaEnrollment->enrollmentPayload($customer);

        if ($customer === null) {
            return ApiResponse::payload([
                'store_id' => $store->id,
                'enrolled' => false,
                'plaid_mode' => $plaidMode,
                ...$dwollaConfig,
                'customer' => null,
                'funding_sources' => [],
                'plaid_pending_verification_accounts' => [],
            ]);
        }

        $profile = is_array($customer->profile) ? $customer->profile : [];

        return ApiResponse::payload([
            'store_id' => $store->id,
            'enrolled' => true,
            'plaid_mode' => $plaidMode,
            ...$dwollaConfig,
            'customer' => [
                'id' => $customer->id,
                'provider' => $customer->provider,
                'external_customer_id' => $customer->external_customer_id,
                'status' => $customer->status,
            ],
            'funding_sources' => $customer->fundingSources->map(fn ($source) => [
                'id' => $source->id,
                'external_funding_source_id' => $source->external_funding_source_id,
                'name' => $source->name,
                'type' => $source->type,
                'status' => $source->status,
                'is_default' => $source->is_default,
            ])->values()->all(),
            'plaid_pending_verification_accounts' => array_values(
                is_array($profile['plaid_pending_verification_accounts'] ?? null)
                    ? $profile['plaid_pending_verification_accounts']
                    : [],
            ),
        ]);
    }

    public function dwollaClientToken(
        DwollaClientTokenRequest $request,
        Store $store,
        DwollaClient $dwolla,
        AchDwollaEnrollmentSession $enrollmentSession,
    ): JsonResponse {
        $this->authorize('view', $store);
        $this->authorize('manage', AchCustomer::class);

        if (! $dwolla->isConfigured()) {
            return response()->json(['message' => 'Dwolla is not configured.'], 422);
        }

        /** @var User $user */
        $user = $request->user();

        try {
            $enrollmentSession->markIntentFromClientToken(
                $store,
                $user,
                (string) $request->validated('action'),
                $request->input('_links'),
            );
            $token = $dwolla->createClientToken($request->dwollaPayload());
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['token' => $token]);
    }

    public function enroll(Request $request, Store $store, AchDwollaEnrollmentService $enrollment): JsonResponse
    {
        $this->authorize('view', $store);
        $this->authorize('manage', AchCustomer::class);

        $validated = $request->validate([
            'sandbox' => ['sometimes', 'boolean'],
            'external_customer_id' => ['sometimes', 'string'],
        ]);

        try {
            if (($validated['sandbox'] ?? false) === true) {
                $customer = $enrollment->sandboxEnroll($store);
            } else {
                /** @var User $user */
                $user = $request->user();
                $customer = $enrollment->enroll(
                    $store,
                    $user,
                    (string) ($validated['external_customer_id'] ?? ''),
                );
            }
        } catch (RuntimeException $exception) {
            return ApiResponse::message($exception->getMessage(), 422);
        }

        return ApiResponse::payload([
            'store_id' => $store->id,
            'enrolled' => true,
            'customer' => [
                'id' => $customer->id,
                'external_customer_id' => $customer->external_customer_id,
                'status' => $customer->status,
            ],
        ], 201);
    }

    public function certifyOwnership(Store $store, AchDwollaEnrollmentService $enrollment): JsonResponse
    {
        $this->authorize('view', $store);
        $this->authorize('manage', AchCustomer::class);

        try {
            $customer = $enrollment->certifyOwnership($store);
        } catch (RuntimeException $exception) {
            return ApiResponse::message($exception->getMessage(), 422);
        }

        return ApiResponse::payload([
            'store_id' => $store->id,
            'ownership_certified' => true,
            'customer' => [
                'id' => $customer->id,
                'external_customer_id' => $customer->external_customer_id,
                'status' => $customer->status,
            ],
        ]);
    }

    public function linkToken(Store $store, AchPlaidLinkService $plaidLink): JsonResponse
    {
        $this->authorize('view', $store);
        $this->authorize('manage', AchCustomer::class);

        $token = $plaidLink->createLinkToken($store);

        return ApiResponse::payload([
            'store_id' => $store->id,
            ...$token,
        ]);
    }

    public function completePlaidLink(Request $request, Store $store, AchPlaidLinkService $plaidLink): JsonResponse
    {
        $this->authorize('view', $store);
        $this->authorize('manage', AchCustomer::class);

        try {
            $result = $plaidLink->completeLink($store, $request->validate([
                'sandbox' => ['sometimes', 'boolean'],
                'public_token' => ['sometimes', 'string'],
                'account_id' => ['sometimes', 'string'],
                'account_name' => ['sometimes', 'nullable', 'string'],
                'verification_status' => ['sometimes', 'nullable', 'string'],
            ]));
        } catch (RuntimeException $exception) {
            return ApiResponse::message($exception->getMessage(), 422);
        }

        return ApiResponse::payload([
            'store_id' => $store->id,
            ...$result,
        ]);
    }
}
