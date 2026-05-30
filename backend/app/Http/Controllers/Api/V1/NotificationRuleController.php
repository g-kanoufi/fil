<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Settings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateNotificationRuleRequest;
use App\Http\Resources\Api\V1\NotificationRuleResource;
use App\Models\NotificationRule;
use App\Services\Notifications\NotificationRulePayloadNormalizer;
use App\Support\Api\ApiResponse;
use App\Support\HtmlSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class NotificationRuleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('manage', Settings::class);

        $query = NotificationRule::query()
            ->withCount('deliveries')
            ->orderBy('title');

        if ($request->boolean('enabled_only')) {
            $query->where('enabled', true);
        }

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($inner) use ($search): void {
                $inner->where('title', 'like', '%'.$search.'%')
                    ->orWhere('trigger_slug', 'like', '%'.$search.'%');
            });
        }

        $rules = $query->get();

        return ApiResponse::collection(NotificationRuleResource::collection($rules));
    }

    public function schema(): JsonResponse
    {
        $this->authorize('manage', Settings::class);

        /** @var array<string, string> $fieldMap */
        $fieldMap = config('fil-notifications.field_map', []);
        /** @var array<string, string> $scheduleFieldMap */
        $scheduleFieldMap = config('fil-notifications.schedule_field_map', []);

        $conditionFields = array_values(array_unique(array_merge(
            array_values($fieldMap),
            array_keys($fieldMap),
            ['lead_status', 'lead_fdd_status', 'lead_temp', 'pipeline_phase'],
        )));

        sort($conditionFields);

        $scheduleFields = array_values(array_unique(array_merge(
            array_values($scheduleFieldMap),
            array_keys($scheduleFieldMap),
        )));

        sort($scheduleFields);

        return ApiResponse::payload([
            'triggers' => config('fil-notifications.triggers', []),
            'condition_fields' => $conditionFields,
            'condition_operators' => [
                ['value' => 'eq', 'label' => 'equals'],
                ['value' => 'neq', 'label' => 'does not equal'],
                ['value' => 'empty', 'label' => 'is empty'],
                ['value' => 'not_empty', 'label' => 'is not empty'],
                ['value' => 'contains', 'label' => 'contains'],
                ['value' => 'not_contains', 'label' => 'does not contain'],
                ['value' => 'lt', 'label' => 'less than'],
                ['value' => 'gt', 'label' => 'greater than'],
                ['value' => 'lte', 'label' => 'less than or equal'],
                ['value' => 'gte', 'label' => 'greater than or equal'],
                ['value' => 'regex', 'label' => 'matches regex'],
                ['value' => 'changed', 'label' => 'changed'],
                ['value' => 'changed_to', 'label' => 'changed to'],
            ],
            'schedule_fields' => $scheduleFields,
            'schedule_directions' => [
                ['value' => 'on', 'label' => 'On date'],
                ['value' => 'after', 'label' => 'After date'],
                ['value' => 'before', 'label' => 'Before date'],
            ],
            'recipient_tokens' => [
                'related:prospect',
                'related:lead_owner',
                'related:area_rep',
            ],
        ]);
    }

    public function show(NotificationRule $notificationRule): JsonResponse
    {
        $this->authorize('manage', Settings::class);

        $notificationRule->loadCount('deliveries');

        return ApiResponse::resource(new NotificationRuleResource($notificationRule));
    }

    public function update(
        UpdateNotificationRuleRequest $request,
        NotificationRule $notificationRule,
        NotificationRulePayloadNormalizer $payloadNormalizer,
        HtmlSanitizer $htmlSanitizer,
    ): JsonResponse {
        $validated = $request->validated();

        if (array_key_exists('body_html', $validated)) {
            $validated['body_html'] = $htmlSanitizer->sanitize(
                is_string($validated['body_html']) ? $validated['body_html'] : null,
            );
        }

        if (array_key_exists('conditionals', $validated)) {
            $validated['conditionals'] = $payloadNormalizer->normalizeConditionals($validated['conditionals']);
        }

        if (array_key_exists('schedule', $validated)) {
            $validated['schedule'] = $payloadNormalizer->normalizeSchedule($validated['schedule']);
        }

        $notificationRule->update($validated);
        $notificationRule->loadCount('deliveries');

        return ApiResponse::resource(new NotificationRuleResource($notificationRule));
    }
}
