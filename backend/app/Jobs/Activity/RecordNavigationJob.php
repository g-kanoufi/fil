<?php

declare(strict_types=1);

namespace App\Jobs\Activity;

use App\Models\User;
use App\Services\Activity\NavigationActivityRecorder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class RecordNavigationJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  list<string>  $paths
     */
    public function __construct(
        public readonly int $actorUserId,
        public readonly array $paths,
    ) {}

    public function handle(NavigationActivityRecorder $recorder): void
    {
        $actor = User::query()->find($this->actorUserId);

        if ($actor === null) {
            return;
        }

        $recorder->upsertBatch($actor, $this->paths);
    }
}
