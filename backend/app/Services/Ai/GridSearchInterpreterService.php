<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\User;
use App\Services\Auth\ResourceScopeService;
use App\Services\Leads\LeadPipelineCatalog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class GridSearchInterpreterService
{
    public function __construct(
        private readonly LeadPipelineCatalog $pipelineCatalog,
    ) {}

    /**
     * @return array{
     *   summary: string,
     *   confidence: string,
     *   source: string,
     *   query: array{search?: string, filters?: array<string, mixed>, sort?: list<array{field: string, direction: string}>},
     *   navigation: array{search?: string, filter?: string, subFilter?: string, leadtemp?: string, sortField?: string, sortDirection?: string}
     * }
     */
    public function interpret(User $user, string $resource, string $naturalLanguageQuery): array
    {
        $query = trim($naturalLanguageQuery);

        if ($query === '') {
            return $this->emptyResult('Enter a question or describe the records you want to see.');
        }

        $config = config("fil-ai-search.resources.{$resource}");

        if ($config === null) {
            abort(404, "Unknown grid resource: {$resource}");
        }

        $aiResult = $this->tryRemoteInterpret($resource, $query, $user);

        if ($aiResult !== null) {
            return $this->finalize($resource, $query, $aiResult, 'ai');
        }

        return $this->finalize($resource, $query, $this->heuristicInterpret($resource, $query), 'heuristic');
    }

    /**
     * @return array{summary: string, confidence: string, query: array<string, mixed>, navigation?: array<string, string>}|null
     */
    private function tryRemoteInterpret(string $resource, string $query, User $user): ?array
    {
        $serviceUrl = config('fil.ai_service_url');

        if (! is_string($serviceUrl) || $serviceUrl === '') {
            return null;
        }

        $response = Http::timeout(45)->post(rtrim($serviceUrl, '/').'/interpret-grid', [
            'resource' => $resource,
            'query' => $query,
            'scope_tier' => app(ResourceScopeService::class)->tier($user),
            'schema' => $this->aiSearchSchema($resource),
            'grid_config' => config("fil-grid.resources.{$resource}"),
        ]);

        if (! $response->successful()) {
            return null;
        }

        $body = $response->json();

        if (! is_array($body)) {
            return null;
        }

        $summary = (string) ($body['summary'] ?? $body['message'] ?? '');

        if ($summary === '') {
            return null;
        }

        return [
            'summary' => $summary,
            'confidence' => (string) ($body['confidence'] ?? 'high'),
            'query' => is_array($body['query'] ?? null) ? $body['query'] : [],
            'navigation' => is_array($body['navigation'] ?? null) ? $body['navigation'] : [],
        ];
    }

    /**
     * @return array{summary: string, confidence: string, query: array<string, mixed>}
     */
    private function heuristicInterpret(string $resource, string $query): array
    {
        $lower = strtolower($query);
        $filters = [];
        $sort = [];
        $search = null;
        $parts = [];

        if ($resource === 'leads') {
            $this->matchLeadHeuristics($lower, $query, $filters, $sort, $search, $parts);
        } elseif ($resource === 'stores') {
            $this->matchStoreHeuristics($lower, $filters, $sort, $search, $parts);
        } else {
            $this->matchContactHeuristics($lower, $filters, $sort, $search, $parts);
        }

        if ($search === null && preg_match('/(?:named|called|for)\s+["\']?([^"\']+)["\']?/i', $query, $matches)) {
            $search = trim($matches[1]);
            $parts[] = 'matching "'.$search.'"';
        }

        if ($search === null && count($filters) === 0 && strlen($query) >= 2) {
            $search = $query;
            $parts[] = 'matching "'.$query.'"';
        }

        if ($sort === []) {
            $sort[] = ['field' => 'updated_at', 'direction' => 'desc'];
        }

        $summary = $parts === []
            ? 'Showing all '.$this->resourceLabel($resource).', newest first.'
            : 'Showing '.$this->resourceLabel($resource).' — '.implode(', ', $parts).'.';

        return [
            'summary' => $summary,
            'confidence' => count($filters) > 0 || $search !== null ? 'medium' : 'low',
            'query' => array_filter([
                'search' => $search,
                'filters' => $filters === [] ? null : $filters,
                'sort' => $sort,
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  list<array{field: string, direction: string}>  $sort
     * @param  list<string>  $parts
     */
    private function matchLeadHeuristics(
        string $lower,
        string $original,
        array &$filters,
        array &$sort,
        ?string &$search,
        array &$parts,
    ): void {
        if (preg_match('/\b(hot|warm|cold)\b/', $lower, $m)) {
            $filters['lead_temp'] = [$m[1], ucfirst($m[1])];
            $parts[] = $m[1].' temperature';
        }

        if (str_contains($lower, 'award')) {
            $filters['lead_fdd_status'] = [
                'award franchise', 'award area', 'award portfolio',
                'Award Franchise (Agreement Signed)', 'Award Area (Master Agreement Signed)',
            ];
            $parts[] = 'awarded deals';
        } elseif (str_contains($lower, 'inactive') || (str_contains($lower, 'closed') && ! str_contains($lower, 'pipeline'))) {
            $filters['lead_fdd_status'] = ['inactive', 'dead deal', 'not qualified', 'close application'];
            $parts[] = 'inactive or closed';
        } elseif (str_contains($lower, 'waiting period') || str_contains($lower, 'in waiting')) {
            $filters['pipeline_phase'] = [8];
            $parts[] = 'in waiting period';
        } elseif (str_contains($lower, 'out of waiting') || str_contains($lower, 'ready to award')) {
            $filters['pipeline_phase'] = [9];
            $parts[] = 'ready to award';
        } elseif (str_contains($lower, 'fdd review') || str_contains($lower, 'viewed fdd')) {
            $filters['pipeline_phase'] = [6];
            $parts[] = 'FDD review stage';
        } elseif (str_contains($lower, 'fdd disclosed') || str_contains($lower, 'fdd sent') || str_contains($lower, 'disclosed')) {
            $filters['pipeline_phase'] = [5];
            $parts[] = 'FDD disclosed';
        } elseif (str_contains($lower, 'new lead') || str_contains($lower, 'intake')) {
            $filters['pipeline_phase'] = [1];
            $parts[] = 'intake / new leads';
        } elseif (str_contains($lower, 'outreach')) {
            $filters['pipeline_phase'] = [2];
            $parts[] = 'outreach stage';
        } elseif (str_contains($lower, 'engaged') || str_contains($lower, 'spoke with')) {
            $filters['pipeline_phase'] = [3];
            $parts[] = 'engaged prospects';
        } elseif (str_contains($lower, 'qualified') || str_contains($lower, 'long form')) {
            $filters['pipeline_phase'] = [4];
            $parts[] = 'qualified applications';
        } elseif (str_contains($lower, 'signed fdd') || str_contains($lower, 'fdd signed')) {
            $filters['pipeline_phase'] = [7];
            $parts[] = 'FDD signed';
        } elseif (str_contains($lower, 'active') && ! str_contains($lower, 'inactive')) {
            $filters['lead_fdd_status'] = ['active', 'disclosed', 'waiting_period', 'new lead'];
            $parts[] = 'active pipeline';
        }

        if (! isset($filters['pipeline_phase'])) {
            foreach ($this->pipelineCatalog->phases() as $phaseId => $phase) {
                if (str_contains($lower, strtolower($phase['label']))) {
                    $filters['pipeline_phase'] = [(int) $phaseId];
                    $parts[] = $phase['label'].' stage';
                    break;
                }
            }
        }

        if (preg_match('/owner\s+([a-z][a-z\s\'.-]{1,40})/i', $original, $m)) {
            $filters['lead_owner'] = trim($m[1]);
            $parts[] = 'owned by '.trim($m[1]);
        }

        if (preg_match('/\b(newest|latest|recent)\b/', $lower)) {
            $sort[] = ['field' => 'updated_at', 'direction' => 'desc'];
            $parts[] = 'sorted by most recently updated';
        } elseif (preg_match('/\b(oldest|earliest)\b/', $lower)) {
            $sort[] = ['field' => 'updated_at', 'direction' => 'asc'];
            $parts[] = 'sorted by oldest first';
        } elseif (preg_match('/sort(?:ed)?\s+by\s+(title|name|phase|pipeline)/', $lower, $m)) {
            $field = str_contains($m[1], 'phase') || str_contains($m[1], 'pipeline') ? 'pipeline_phase' : 'title';
            $direction = str_contains($lower, 'desc') || str_contains($lower, 'z-a') ? 'desc' : 'asc';
            $sort[] = ['field' => $field, 'direction' => $direction];
            $parts[] = 'sorted by '.$field;
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  list<array{field: string, direction: string}>  $sort
     * @param  list<string>  $parts
     */
    private function matchStoreHeuristics(string $lower, array &$filters, array &$sort, ?string &$search, array &$parts): void
    {
        foreach (['open', 'pending', 'closed'] as $status) {
            if (str_contains($lower, $status)) {
                $filters['store_status'] = [$status, ucfirst($status)];
                $parts[] = $status.' stores';
                break;
            }
        }

        if (preg_match('/\b(newest|latest|recent)\b/', $lower)) {
            $sort[] = ['field' => 'updated_at', 'direction' => 'desc'];
            $parts[] = 'newest first';
        } elseif (preg_match('/\b(oldest|earliest)\b/', $lower)) {
            $sort[] = ['field' => 'updated_at', 'direction' => 'asc'];
        } elseif (str_contains($lower, 'name')) {
            $sort[] = ['field' => 'name', 'direction' => str_contains($lower, 'z-a') ? 'desc' : 'asc'];
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  list<array{field: string, direction: string}>  $sort
     * @param  list<string>  $parts
     */
    private function matchContactHeuristics(string $lower, array &$filters, array &$sort, ?string &$search, array &$parts): void
    {
        foreach (['franchisor', 'lead_owner', 'admin', 'prospect'] as $role) {
            if (str_contains($lower, str_replace('_', ' ', $role)) || str_contains($lower, $role)) {
                $filters['role'] = $role;
                $parts[] = $role.' contacts';
                break;
            }
        }

        if (str_contains($lower, 'email') || str_contains($lower, '@')) {
            $sort[] = ['field' => 'email', 'direction' => 'asc'];
        } elseif (preg_match('/\b(newest|latest|recent)\b/', $lower)) {
            $sort[] = ['field' => 'updated_at', 'direction' => 'desc'];
        } else {
            $sort[] = ['field' => 'name', 'direction' => 'asc'];
        }
    }

    /**
     * @param  array{summary: string, confidence: string, query: array<string, mixed>, navigation?: array<string, string>}  $interpreted
     * @return array{summary: string, confidence: string, source: string, query: array<string, mixed>, navigation: array<string, string>}
     */
    private function finalize(string $resource, string $originalQuery, array $interpreted, string $source): array
    {
        $gridConfig = config("fil-grid.resources.{$resource}", []);
        $query = $this->sanitizeQuery(
            is_array($interpreted['query'] ?? null) ? $interpreted['query'] : [],
            $gridConfig,
        );

        $navigation = $this->buildNavigation($resource, $query, $interpreted['navigation'] ?? []);

        return [
            'summary' => $interpreted['summary'],
            'confidence' => $interpreted['confidence'] ?? 'medium',
            'source' => $source,
            'query' => $query,
            'navigation' => $navigation,
            'original_query' => $originalQuery,
        ];
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $gridConfig
     * @return array{search?: string, filters?: array<string, mixed>, sort?: list<array{field: string, direction: string}>}
     */
    private function sanitizeQuery(array $query, array $gridConfig): array
    {
        $filterable = $gridConfig['filterable'] ?? [];
        $sortable = $gridConfig['sortable'] ?? [];
        $sanitized = [];

        if (isset($query['search']) && is_string($query['search']) && trim($query['search']) !== '') {
            $sanitized['search'] = Str::limit(trim($query['search']), 255, '');
        }

        if (isset($query['filters']) && is_array($query['filters'])) {
            $filters = [];

            foreach ($query['filters'] as $key => $value) {
                if (! in_array($key, $filterable, true)) {
                    continue;
                }

                $filters[$key] = $value;
            }

            if ($filters !== []) {
                $sanitized['filters'] = $filters;
            }
        }

        if (isset($query['sort']) && is_array($query['sort'])) {
            $sort = [];

            foreach ($query['sort'] as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $field = (string) ($item['field'] ?? '');
                $direction = strtolower((string) ($item['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

                if ($field !== '' && in_array($field, $sortable, true)) {
                    $sort[] = ['field' => $field, 'direction' => $direction];
                }
            }

            if ($sort !== []) {
                $sanitized['sort'] = $sort;
            }
        }

        if (! isset($sanitized['sort'])) {
            $default = $gridConfig['default_sort'] ?? ['field' => 'updated_at', 'direction' => 'desc'];
            $sanitized['sort'] = [[
                'field' => (string) ($default['field'] ?? 'updated_at'),
                'direction' => (string) ($default['direction'] ?? 'desc'),
            ]];
        }

        return $sanitized;
    }

    /**
     * @param  array{search?: string, filters?: array<string, mixed>, sort?: list<array{field: string, direction: string}>}  $query
     * @param  array<string, string>  $hint
     * @return array<string, string>
     */
    private function buildNavigation(string $resource, array $query, array $hint): array
    {
        $navigation = array_filter([
            'search' => $query['search'] ?? '',
            'sortField' => $query['sort'][0]['field'] ?? 'updated_at',
            'sortDirection' => $query['sort'][0]['direction'] ?? 'desc',
        ], fn ($value) => $value !== null && $value !== '');

        if ($hint !== []) {
            return array_merge($navigation, array_filter($hint, fn ($v) => $v !== null && $v !== ''));
        }

        $filters = $query['filters'] ?? [];

        if ($resource === 'leads') {
            if (isset($filters['lead_temp']) && is_array($filters['lead_temp'])) {
                $temp = strtolower((string) $filters['lead_temp'][0]);
                $navigation['leadtemp'] = $temp;
            }

            if (isset($filters['lead_owner'])) {
                $navigation['filter'] = 'lead_owner';
                $navigation['subFilter'] = Str::slug((string) $filters['lead_owner'], '_');
            } elseif (isset($filters['lead_fdd_status'])) {
                $navigation['filter'] = 'lead_status';
                $values = (array) $filters['lead_fdd_status'];
                $first = strtolower((string) ($values[0] ?? ''));

                if (str_contains($first, 'award')) {
                    $navigation['subFilter'] = 'leads_awarded_deals';
                } elseif ($first === 'active' || str_contains($first, 'disclosed')) {
                    $navigation['subFilter'] = 'leads_active';
                } else {
                    $navigation['subFilter'] = Str::slug($first, '_');
                }
            } elseif (isset($filters['pipeline_phase'])) {
                $navigation['filter'] = 'lead_status';
            }
        }

        return $navigation;
    }

    /**
     * @return array{summary: string, confidence: string, source: string, query: array<string, mixed>, navigation: array<string, string>, original_query: string}
     */
    private function emptyResult(string $summary): array
    {
        return [
            'summary' => $summary,
            'confidence' => 'low',
            'source' => 'none',
            'query' => [],
            'navigation' => [],
            'original_query' => '',
        ];
    }

    private function resourceLabel(string $resource): string
    {
        return match ($resource) {
            'leads' => 'applications',
            'stores' => 'stores',
            'contacts' => 'contacts',
            default => $resource,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function aiSearchSchema(string $resource): array
    {
        /** @var array<string, mixed> $schema */
        $schema = config("fil-ai-search.resources.{$resource}", []);

        if ($resource === 'leads') {
            $schema['pipeline_labels'] = collect($this->pipelineCatalog->phases())
                ->mapWithKeys(fn (array $phase, int $id): array => [$id => $phase['label']])
                ->all();
        }

        return $schema;
    }
}
