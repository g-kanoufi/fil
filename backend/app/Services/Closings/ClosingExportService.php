<?php

declare(strict_types=1);

namespace App\Services\Closings;

use App\Models\Closing;
use App\Models\User;
use App\Services\Auth\ResourceScopeService;
use Illuminate\Database\Eloquent\Builder;

final class ClosingExportService
{
    public function __construct(
        private readonly ClosingWorkflowCatalog $workflow,
        private readonly ResourceScopeService $scope,
    ) {}

    /**
     * @return list<array<string, string|int|null>>
     */
    public function rowsForUser(User $user): array
    {
        $query = Closing::query()
            ->with(['lead:id,title', 'store:id,title', 'area:id,name'])
            ->orderByDesc('closing_date')
            ->limit((int) config('fil-closings.max_export_rows', 5000));

        $this->scope->applyClosingScope($query, $user);

        $rows = [];

        /** @var Builder<Closing> $query */
        foreach ($query->cursor() as $closing) {
            $feeLines = $this->workflow->feeLinesFromExtras($closing->extras);
            $feeTotal = array_sum(array_column($feeLines, 'amount_cents'));

            if ($feeLines === []) {
                $rows[] = $this->row($closing, null, $feeTotal);

                continue;
            }

            foreach ($feeLines as $line) {
                $rows[] = $this->row($closing, $line, $feeTotal);
            }
        }

        return $rows;
    }

    /**
     * @param  array{label: string, amount_cents: int}|null  $feeLine
     * @return array<string, string|int|null>
     */
    private function row(Closing $closing, ?array $feeLine, int $feeTotalCents): array
    {
        return [
            'closing_id' => $closing->id,
            'title' => $closing->title,
            'status' => (string) $closing->status,
            'status_label' => $this->workflow->label((string) $closing->status),
            'closing_date' => $closing->closing_date?->toDateString(),
            'lead' => $closing->lead?->title,
            'store' => $closing->store?->title,
            'area' => $closing->area?->name,
            'fee_label' => $feeLine['label'] ?? null,
            'fee_amount_cents' => $feeLine['amount_cents'] ?? null,
            'fee_total_cents' => $feeTotalCents,
        ];
    }
}
