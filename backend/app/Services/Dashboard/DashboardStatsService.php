<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Models\FddDelivery;
use App\Models\Lead;
use App\Models\Store;
use App\Models\User;
use App\Services\Auth\ResourceScopeService;
use App\Services\Leads\LeadPipelineCatalog;
use Illuminate\Support\Collection;

final class DashboardStatsService
{
    public function __construct(
        private readonly LeadPipelineCatalog $pipelineCatalog,
        private readonly ResourceScopeService $scope,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function stats(User $user, int $months = 6): array
    {
        $leadQuery = Lead::query()->where('status', 'active');
        $storeQuery = Store::query()->where('status', 'active');

        if ($this->scope->mayListLeads($user)) {
            $this->scope->applyLeadScope($leadQuery, $user);
        } else {
            $leadQuery->whereRaw('1 = 0');
        }

        $this->scope->applyStoreScope($storeQuery, $user);

        /** @var Collection<int, Lead> $activeLeads */
        $activeLeads = (clone $leadQuery)->get([
            'id', 'title', 'lead_fdd_status', 'lead_status', 'lead_temp',
            'pipeline_phase', 'fdd_signed_at', 'disclosed_at', 'updated_at',
        ]);

        $statusCounts = [];

        foreach ($activeLeads as $lead) {
            $label = $this->pipelineCatalog->applicationStatusLabel($lead);
            $statusCounts[$label] = ($statusCounts[$label] ?? 0) + 1;
        }

        arsort($statusCounts);

        $leadsByStatus = collect($statusCounts)
            ->take(12)
            ->map(fn (int $count, string $status) => ['status' => $status, 'count' => $count])
            ->values()
            ->all();

        $phaseCounts = [];

        foreach ($activeLeads as $lead) {
            $presentation = $this->pipelineCatalog->presentation($lead);
            $label = $presentation['pipeline_phase_label'];
            $phaseCounts[$label] = ($phaseCounts[$label] ?? 0) + 1;
        }

        ksort($phaseCounts);

        $recentLeads = (clone $leadQuery)
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get([
                'id', 'title', 'lead_fdd_status', 'lead_status', 'lead_temp',
                'pipeline_phase', 'fdd_signed_at', 'disclosed_at', 'updated_at',
            ])
            ->map(function (Lead $lead): array {
                $presentation = $this->pipelineCatalog->presentation($lead);

                return [
                    'id' => $lead->id,
                    'title' => $lead->title,
                    'status' => $presentation['application_status_label'],
                    'temp' => $lead->lead_temp,
                    'pipeline_phase' => $presentation['pipeline_phase'],
                    'pipeline_phase_label' => $presentation['pipeline_phase_label'],
                    'updated_at' => $lead->updated_at?->toIso8601String(),
                ];
            })
            ->all();

        $fddSent30d = FddDelivery::query()
            ->where('sent_at', '>=', now()->subDays(30))
            ->count();

        return [
            'leads' => [
                'total' => $activeLeads->count(),
                'by_status' => $leadsByStatus,
                'added_monthly' => $this->monthlyLeadCounts($user, $months),
            ],
            'stores' => [
                'total' => (clone $storeQuery)->count(),
            ],
            'fdd_deliveries' => [
                'sent_30d' => $fddSent30d,
                'total' => FddDelivery::query()->count(),
                'sent_monthly' => $this->monthlyFddCounts($months),
            ],
            'recent_leads' => $recentLeads,
            'pipeline' => collect($phaseCounts)
                ->map(fn (int $count, string $label) => [
                    'phase' => $label,
                    'label' => $label,
                    'count' => $count,
                ])
                ->values()
                ->all(),
            'pipeline_phases' => collect($this->pipelineCatalog->phases())
                ->map(fn (array $phase, int $key) => [
                    'id' => $key,
                    'label' => $phase['label'],
                    'description' => $phase['description'],
                ])
                ->values()
                ->all(),
            'chart_months' => $months,
        ];
    }

    /**
     * @return list<array{month: string, label: string, count: int}>
     */
    private function monthlyLeadCounts(User $user, int $months): array
    {
        $series = [];

        for ($offset = $months - 1; $offset >= 0; $offset--) {
            $start = now()->subMonths($offset)->startOfMonth();
            $end = (clone $start)->endOfMonth();

            $query = Lead::query()->whereBetween('created_at', [$start, $end]);

            if ($this->scope->mayListLeads($user)) {
                $this->scope->applyLeadScope($query, $user);
            } else {
                $query->whereRaw('1 = 0');
            }

            $series[] = [
                'month' => $start->format('Y-m'),
                'label' => $start->format('M'),
                'count' => $query->count(),
            ];
        }

        return $series;
    }

    /**
     * @return list<array{month: string, label: string, count: int}>
     */
    private function monthlyFddCounts(int $months): array
    {
        $series = [];

        for ($offset = $months - 1; $offset >= 0; $offset--) {
            $start = now()->subMonths($offset)->startOfMonth();
            $end = (clone $start)->endOfMonth();

            $series[] = [
                'month' => $start->format('Y-m'),
                'label' => $start->format('M'),
                'count' => FddDelivery::query()
                    ->whereNotNull('sent_at')
                    ->whereBetween('sent_at', [$start, $end])
                    ->count(),
            ];
        }

        return $series;
    }
}
