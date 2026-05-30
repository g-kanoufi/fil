<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Notifications\NotificationPreferenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class NotificationPreferenceController extends Controller
{
    public function index(Request $request, NotificationPreferenceService $preferences): JsonResponse
    {
        $user = $request->user();
        $list = (string) $request->query('list', 'platform');

        return response()->json([
            'data' => $preferences->profileListFor($user, $list),
        ]);
    }

    public function update(Request $request, NotificationPreferenceService $preferences): JsonResponse
    {
        $validated = $request->validate([
            'preferences' => ['required', 'array'],
            'preferences.*' => ['boolean'],
        ]);

        /** @var array<int, bool> $prefs */
        $prefs = [];

        foreach ($validated['preferences'] as $ruleId => $optedIn) {
            $prefs[(int) $ruleId] = (bool) $optedIn;
        }

        $preferences->syncPreferences($request->user(), $prefs);

        return response()->json(['data' => ['saved' => true]]);
    }
}
