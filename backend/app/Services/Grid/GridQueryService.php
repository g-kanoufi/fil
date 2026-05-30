<?php

declare(strict_types=1);

namespace App\Services\Grid;

use App\Models\Lead;
use App\Models\User;
use App\Services\Auth\ResourceScopeService;
use App\Services\Leads\LeadPipelineCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class GridQueryService
{
    public function __construct(
        private readonly LeadPipelineCatalog $pipelineCatalog,
        private readonly ResourceScopeService $scope,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function query(User $user, string $resource, array $payload): array
    {
        $config = config("fil-grid.resources.{$resource}");

        if ($config === null) {
            abort(404, "Unknown grid resource: {$resource}");
        }

        $this->authorizeResource($user, $config);

        $filters = (array) ($payload['filters'] ?? []);
        $search = isset($payload['search']) ? (string) $payload['search'] : null;
        $limit = min(max((int) ($payload['limit'] ?? 50), 1), 200);
        $aggregationOnly = ($payload['size'] ?? null) === 0
            || (($payload['include_aggregations'] ?? false) && ! isset($payload['limit']));

        /** @var Builder<Model> $query */
        $query = $config['model']::query();

        if (! ($config['skip_status_filter'] ?? false)) {
            $query->where('status', $config['default_status'] ?? 'active');
        }

        if ($resource === 'leads') {
            $query->with('owner:id,first_name,last_name,name');
        }

        if ($resource === 'contacts') {
            $query->whereHas('roles', fn (Builder $roleQuery) => $roleQuery->where('name', '!=', 'prospect'));
            $query->with('roles:id,name');
        }

        if ($resource === 'stores') {
            $query->with('area:id,name');
        }

        $this->applyResourceScope($query, $user, $resource);

        $this->applyFilters($query, $filters, $config);
        $this->applySearch($query, $search, $config);

        $includeAggregations = ($payload['include_aggregations'] ?? true) !== false;
        $sortItems = $this->resolveSortItems((array) ($payload['sort'] ?? []), $config);

        $aggregations = ($includeAggregations || $aggregationOnly)
            ? $this->buildAggregations($resource, $query, $config, $payload, $filters)
            : [];

        if ($aggregationOnly) {
            return [
                'hits' => ['total' => ['value' => 0], 'hits' => []],
                'aggregations' => $aggregations,
                'meta' => ['next_cursor' => null],
            ];
        }

        $total = (clone $query)->count();
        $this->applySort($query, $sortItems, $config);
        $this->applyCursor($query, $payload['cursor'] ?? null, $sortItems, $config);

        $rows = $query->limit($limit + 1)->get();
        $hasMore = $rows->count() > $limit;
        $page = $hasMore ? $rows->slice(0, $limit) : $rows;

        $hits = $page->map(fn ($row) => $this->mapHit($resource, $row))->values()->all();
        $nextCursor = $hasMore ? $this->encodeCursor($page->last(), $sortItems, $config) : null;

        $response = [
            'hits' => [
                'total' => ['value' => $total],
                'hits' => $hits,
            ],
            'meta' => ['next_cursor' => $nextCursor],
        ];

        if ($includeAggregations) {
            $response['aggregations'] = $aggregations;
        }

        return $response;
    }

    /**
     * @param  list<array{field: string, direction: string}>  $sortItems
     * @param  array<string, mixed>  $config
     * @return list<array{field: string, direction: string}>
     */
    private function resolveSortItems(array $sort, array $config): array
    {
        $allowed = $config['sortable'] ?? [];

        if ($sort === []) {
            $default = $config['default_sort'] ?? ['field' => 'updated_at', 'direction' => 'desc'];

            return [['field' => (string) $default['field'], 'direction' => strtolower((string) ($default['direction'] ?? 'desc'))]];
        }

        $resolved = [];

        foreach ($sort as $item) {
            $field = $item['field'] ?? null;
            $direction = strtolower($item['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

            if ($field !== null && in_array($field, $allowed, true)) {
                $resolved[] = ['field' => $field, 'direction' => $direction];
            }
        }

        if ($resolved === []) {
            $default = $config['default_sort'] ?? ['field' => 'updated_at', 'direction' => 'desc'];

            return [['field' => (string) $default['field'], 'direction' => strtolower((string) ($default['direction'] ?? 'desc'))]];
        }

        return $resolved;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function authorizeResource(User $user, array $config): void
    {
        $ability = (string) ($config['policy'] ?? 'viewAny');
        $model = $config['policy_model'] ?? $config['model'];

        if (! $user->can($ability, $model)) {
            abort(403, 'Forbidden.');
        }
    }

    /**
     * @param  Builder<Model>  $query
     */
    private function applyResourceScope(Builder $query, User $user, string $resource): void
    {
        match ($resource) {
            'leads' => $this->scope->applyLeadScope($query, $user),
            'stores' => $this->scope->applyStoreScope($query, $user),
            'contacts' => $this->scope->applyContactScope($query, $user),
            default => null,
        };
    }

    /**
     * @param  Builder<Model>  $query
     * @param  array<string, mixed>  $config
     */
    private function applyFilters(Builder $query, array $filters, array $config): void
    {
        $allowed = $config['filterable'] ?? [];

        foreach ($filters as $field => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if ($field === 'lead_owner') {
                $names = is_array($value) ? $value : [$value];
                $query->whereHas('owner', function (Builder $owner) use ($names): void {
                    $owner->where(function (Builder $inner) use ($names): void {
                        foreach ($names as $name) {
                            $needle = '%'.trim((string) $name).'%';
                            if (DB::connection()->getDriverName() === 'pgsql') {
                                $inner->orWhere('name', 'ilike', $needle)
                                    ->orWhereRaw("TRIM(CONCAT(first_name, ' ', last_name)) ILIKE ?", [$needle]);
                            } else {
                                $inner->orWhereRaw('LOWER(name) LIKE ?', [strtolower($needle)])
                                    ->orWhereRaw("LOWER(TRIM(first_name || ' ' || last_name)) LIKE ?", [strtolower($needle)]);
                            }
                        }
                    });
                });

                continue;
            }

            if ($field === 'role') {
                $roles = is_array($value) ? $value : [$value];
                $query->whereHas('roles', fn (Builder $roleQuery) => $roleQuery->whereIn('name', $roles));

                continue;
            }

            if (! in_array($field, $allowed, true)) {
                continue;
            }

            if ($field === 'area_id') {
                if (is_array($value)) {
                    $query->whereIn($field, array_map(intval(...), $value));
                } else {
                    $query->where($field, (int) $value);
                }

                continue;
            }

            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }
    }

    /**
     * @param  Builder<Model>  $query
     * @param  array<string, mixed>  $config
     */
    private function applySearch(Builder $query, ?string $search, array $config): void
    {
        if ($search === null || $search === '') {
            return;
        }

        $columns = $config['search_columns'] ?? [];
        $needle = '%'.$search.'%';

        $query->where(function (Builder $inner) use ($columns, $needle): void {
            foreach ($columns as $column) {
                if (DB::connection()->getDriverName() === 'pgsql') {
                    $inner->orWhere($column, 'ilike', $needle);
                } else {
                    $inner->orWhereRaw('LOWER('.$column.') LIKE ?', [strtolower($needle)]);
                }
            }
        });
    }

    /**
     * @param  Builder<Model>  $query
     * @param  list<array{field?: string, direction?: string}>  $sort
     * @param  array<string, mixed>  $config
     */
    private function applySort(Builder $query, array $sort, array $config): void
    {
        $allowed = $config['sortable'] ?? [];

        if ($sort === []) {
            $default = $config['default_sort'] ?? ['field' => 'updated_at', 'direction' => 'desc'];
            $sort = [$default];
        }

        foreach ($sort as $item) {
            $field = $item['field'] ?? null;
            $direction = strtolower($item['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

            if ($field !== null && in_array($field, $allowed, true)) {
                $query->orderBy($field, $direction);
            }
        }

        $query->orderBy('id', 'desc');
    }

    /**
     * @param  Builder<Model>  $query
     * @param  list<array{field: string, direction: string}>  $sortItems
     * @param  array<string, mixed>  $config
     */
    private function applyCursor(Builder $query, mixed $cursor, array $sortItems, array $config): void
    {
        if (! is_string($cursor) || $cursor === '') {
            return;
        }

        $decoded = json_decode(base64_decode($cursor, true) ?: '', true);

        if (! is_array($decoded) || ! isset($decoded['id'])) {
            return;
        }

        $primary = $sortItems[0] ?? ['field' => 'updated_at', 'direction' => 'desc'];
        $sortField = $primary['field'];
        $sortDirection = $primary['direction'] ?? 'desc';
        $sortValue = $this->normalizeCursorSortValue($decoded[$sortField] ?? null);
        $primaryOp = $sortDirection === 'desc' ? '<' : '>';
        $tieIdOp = $sortDirection === 'desc' ? '<' : '>';

        $query->where(function (Builder $inner) use ($sortField, $sortValue, $decoded, $primaryOp, $tieIdOp): void {
            if ($sortValue !== null) {
                $inner->where($sortField, $primaryOp, $sortValue)
                    ->orWhere(function (Builder $tie) use ($sortField, $sortValue, $decoded, $tieIdOp): void {
                        $tie->where($sortField, '=', $sortValue)
                            ->where('id', $tieIdOp, $decoded['id']);
                    });
            } else {
                $inner->where('id', $tieIdOp, $decoded['id']);
            }
        });
    }

    private function normalizeCursorSortValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_string($value) && strtotime($value) !== false) {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        }

        return is_scalar($value) ? (string) $value : null;
    }

    /**
     * @param  Model  $row
     * @return array<string, mixed>
     */
    private function mapHit(string $resource, $row): array
    {
        if ($resource === 'leads') {
            /** @var Lead $row */
            $presentation = $this->pipelineCatalog->presentation($row);
            $statusLabel = $presentation['application_status_label'];

            return [
                '_source' => [
                    'id' => $row->id,
                    'post_title' => $row->title,
                    'updated_at' => $row->updated_at,
                    'meta' => [
                        'lead_status' => $statusLabel,
                        'lead_stage' => $row->lead_stage,
                        'lead_owner' => $row->owner?->name,
                        'lead_temp' => $row->lead_temp,
                        'lead_source' => $row->lead_source,
                        'likelihood_to_close' => $row->likelihood_to_close,
                        'pipeline_phase' => $presentation['pipeline_phase_label'],
                    ],
                ],
            ];
        }

        if ($resource === 'stores') {
            return [
                '_source' => [
                    'id' => $row->id,
                    'post_title' => $row->name,
                    'updated_at' => $row->updated_at,
                    'meta' => [
                        'stores' => $row->store_status,
                        'store_status' => $row->store_status,
                        'area_id' => $row->area_id,
                        'areas' => $row->area?->name ?? 'Not defined',
                        'area' => $row->area?->name ?? 'Not defined',
                    ],
                ],
            ];
        }

        if ($resource === 'contacts') {
            /** @var User $row */
            $displayName = trim((string) (($row->first_name ?? '').' '.($row->last_name ?? '')));

            return [
                '_source' => [
                    'id' => $row->id,
                    'post_title' => $displayName !== '' ? $displayName : $row->name,
                    'updated_at' => $row->updated_at,
                    'meta' => [
                        'contacts' => $row->roles->first()?->name,
                        'email' => $row->email,
                    ],
                ],
            ];
        }

        return ['_source' => ['id' => $row->id]];
    }

    /**
     * @param  Builder<Model>  $query
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $filters
     * @return array<string, array{buckets: list<array{key: string|int|float|null, doc_count: int}>}>
     */
    private function buildAggregations(string $resource, Builder $query, array $config, array $payload, array $filters): array
    {
        $definitions = $config['aggregations'] ?? [];
        $requested = $payload['aggregations'] ?? null;

        if (is_array($requested) && $requested !== []) {
            $definitions = array_intersect_key(
                $definitions,
                array_flip(array_map(fn ($key) => $this->normalizeAggKey((string) $key), $requested)),
            );
        }

        $result = [];
        $table = (string) $config['table'];

        foreach ($definitions as $alias => $definition) {
            $column = $definition['column'];
            $type = $definition['type'] ?? 'terms';

            $aggQuery = clone $query;
            $this->applyFiltersExcept($aggQuery, $filters, $config, $column);

            if ($type === 'owner_name' && $resource === 'leads') {
                $nameExpr = DB::connection()->getDriverName() === 'pgsql'
                    ? "COALESCE(NULLIF(TRIM(CONCAT(users.first_name, ' ', users.last_name)), ''), users.name, 'Not defined')"
                    : "COALESCE(NULLIF(TRIM(users.first_name || ' ' || users.last_name), ''), users.name, 'Not defined')";

                $buckets = DB::table('leads')
                    ->join('users', 'users.id', '=', 'leads.owner_user_id')
                    ->whereIn('leads.id', (clone $aggQuery)->select('leads.id'))
                    ->selectRaw("{$nameExpr} AS key")
                    ->selectRaw('COUNT(*) AS doc_count')
                    ->groupBy('key')
                    ->orderByDesc('doc_count')
                    ->limit(100)
                    ->get()
                    ->map(fn ($row) => ['key' => $row->key, 'doc_count' => (int) $row->doc_count])
                    ->all();
            } elseif ($type === 'role_name' && $resource === 'contacts') {
                $buckets = DB::table('users')
                    ->join('model_has_roles', function ($join): void {
                        $join->on('model_has_roles.model_id', '=', 'users.id')
                            ->where('model_has_roles.model_type', '=', User::class);
                    })
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->whereIn('users.id', (clone $aggQuery)->select('users.id'))
                    ->where('roles.name', '!=', 'prospect')
                    ->selectRaw('roles.name AS key')
                    ->selectRaw('COUNT(DISTINCT users.id) AS doc_count')
                    ->groupBy('roles.name')
                    ->orderByDesc('doc_count')
                    ->limit(100)
                    ->get()
                    ->map(fn ($row) => ['key' => $row->key, 'doc_count' => (int) $row->doc_count])
                    ->all();
            } elseif ($type === 'area_name') {
                $buckets = DB::table($table)
                    ->leftJoin('areas', 'areas.id', '=', "{$table}.area_id")
                    ->whereIn("{$table}.id", (clone $aggQuery)->select("{$table}.id"))
                    ->selectRaw("COALESCE(CAST({$table}.area_id AS TEXT), 'Not defined') AS key")
                    ->selectRaw("COALESCE(areas.name, 'Not defined') AS label")
                    ->selectRaw('COUNT(*) AS doc_count')
                    ->groupBy("{$table}.area_id", 'areas.name')
                    ->orderBy('areas.name')
                    ->limit(100)
                    ->get()
                    ->map(fn ($row) => [
                        'key' => $row->key,
                        'label' => $row->label,
                        'doc_count' => (int) $row->doc_count,
                    ])
                    ->all();
            } else {
                $buckets = DB::table($table)
                    ->whereIn('id', (clone $aggQuery)->select('id'))
                    ->selectRaw("COALESCE(CAST({$column} AS TEXT), 'Not defined') AS key")
                    ->selectRaw('COUNT(*) AS doc_count')
                    ->groupBy('key')
                    ->orderByDesc('doc_count')
                    ->limit(100)
                    ->get()
                    ->map(fn ($row) => ['key' => $row->key, 'doc_count' => (int) $row->doc_count])
                    ->all();
            }

            if ($resource === 'leads' && $alias === 'meta.lead_status') {
                $buckets = $this->normalizeLeadStatusBuckets($buckets);
            }

            $result[$alias] = ['buckets' => $buckets];
        }

        return $result;
    }

    /**
     * @param  list<array{key: mixed, doc_count: int}>  $buckets
     * @return list<array{key: string, label: string, doc_count: int}>
     */
    private function normalizeLeadStatusBuckets(array $buckets): array
    {
        $merged = [];

        foreach ($buckets as $bucket) {
            $label = $this->pipelineCatalog->normalizeAggregationKey($bucket['key']);

            if (! isset($merged[$label])) {
                $merged[$label] = ['key' => $label, 'label' => $label, 'doc_count' => 0];
            }

            $merged[$label]['doc_count'] += (int) $bucket['doc_count'];
        }

        usort($merged, fn (array $a, array $b): int => $b['doc_count'] <=> $a['doc_count']);

        return array_values($merged);
    }

    /**
     * @param  Builder<Model>  $query
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $config
     */
    private function applyFiltersExcept(Builder $query, array $filters, array $config, string $exceptColumn): void
    {
        $allowed = $config['filterable'] ?? [];

        foreach ($filters as $field => $value) {
            if ($field === $exceptColumn || ! in_array($field, $allowed, true) || $value === null || $value === '') {
                continue;
            }

            if ($field === 'area_id') {
                if (is_array($value)) {
                    $query->whereIn($field, array_map(intval(...), $value));
                } else {
                    $query->where($field, (int) $value);
                }

                continue;
            }

            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }
    }

    private function normalizeAggKey(string $key): string
    {
        return str_starts_with($key, 'meta.') ? $key : 'meta.'.$key;
    }

    /**
     * @param  list<array{field: string, direction: string}>  $sortItems
     * @param  array<string, mixed>  $config
     */
    private function encodeCursor(object $row, array $sortItems, array $config): string
    {
        $sortField = $sortItems[0]['field'] ?? ($config['default_sort']['field'] ?? 'updated_at');
        $sortValue = $this->normalizeCursorSortValue($row->{$sortField} ?? null);

        return base64_encode(json_encode([
            'id' => $row->id,
            $sortField => $sortValue,
        ], JSON_THROW_ON_ERROR));
    }
}
