<?php

declare(strict_types=1);

namespace App\Services\Activity;

use App\Models\ActivityEvent;
use App\Models\Lead;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class ActivityRecorder
{
    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function record(
        string $category,
        string $action,
        string $summary,
        ?User $actor = null,
        ?Model $subject = null,
        ?Model $object = null,
        ?array $payload = null,
        string $source = 'app',
    ): ActivityEvent {
        $this->assertVocabulary($category, $action);

        $subjectType = null;
        $subjectId = null;

        if ($subject instanceof Model) {
            [$subjectType, $subjectId] = $this->resolveSubject($subject);
        }

        $objectType = null;
        $objectId = null;

        if ($object instanceof Model) {
            [$objectType, $objectId] = $this->resolveSubject($object);
        }

        return ActivityEvent::query()->create([
            'occurred_at' => now(),
            'actor_user_id' => $actor?->id,
            'actor_name' => $actor?->name ?? 'System',
            'category' => $category,
            'action' => $action,
            'summary' => Str::limit($summary, 500, ''),
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'object_type' => $objectType,
            'object_id' => $objectId,
            'source' => $source,
            'payload' => $payload,
        ]);
    }

    private function assertVocabulary(string $category, string $action): void
    {
        $categories = config('fil-activity.categories', []);
        $actions = config('fil-activity.actions', []);

        if (! in_array($category, $categories, true)) {
            throw new InvalidArgumentException("Unknown activity category: {$category}");
        }

        if (! in_array($action, $actions, true)) {
            throw new InvalidArgumentException("Unknown activity action: {$action}");
        }
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function resolveSubject(Model $model): array
    {
        return match ($model::class) {
            Lead::class => ['lead', (int) $model->getKey()],
            Store::class => ['store', (int) $model->getKey()],
            User::class => ['user', (int) $model->getKey()],
            default => [Str::snake(class_basename($model)), (int) $model->getKey()],
        };
    }
}
