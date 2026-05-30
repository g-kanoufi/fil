<?php

declare(strict_types=1);

namespace App\Jobs\Documents;

use App\Services\Documents\DocumentExportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class ProcessDocumentExportJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(
        public readonly string $exportId,
    ) {}

    public function handle(DocumentExportService $exports): void
    {
        $exports->process($this->exportId);
    }
}
