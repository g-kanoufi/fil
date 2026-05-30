<?php

declare(strict_types=1);

namespace App\Jobs\Communications;

use App\Actions\Communications\SendStaffCommunication;
use App\Models\Communication;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

final class SendStaffCommunicationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [30, 120, 300];

    public function __construct(
        public readonly int $communicationId,
    ) {}

    public function handle(SendStaffCommunication $send): void
    {
        $communication = Communication::query()->find($this->communicationId);

        if ($communication === null || $communication->status !== 'pending') {
            return;
        }

        $send->deliver($communication);
    }

    public function failed(?Throwable $exception): void
    {
        $communication = Communication::query()->find($this->communicationId);

        if ($communication === null || $communication->status !== 'pending') {
            return;
        }

        $communication->update([
            'status' => 'failed',
            'errors' => $exception?->getMessage() ?? 'Communication delivery failed',
        ]);
    }
}
