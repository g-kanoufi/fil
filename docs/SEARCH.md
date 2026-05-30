# FIL search & fast grids (no Elasticsearch)

The legacy stack used ElasticSearch + a PHP proxy for AG Grid infinite scroll **and** sidebar facet counts, dashboard charts, and dynamic filter menus. FIL replaces the search cluster with **PostgreSQL + a thin query API**, keeping the same UX — **including aggregations**.

## API

`POST /api/v1/query/{resource}` where `resource` is `leads`, `stores`, or `contacts`.

### Row query (infinite grid)

```json
{
  "search": "smith",
  "filters": { "lead_status": "active" },
  "sort": [{ "field": "updated_at", "direction": "desc" }],
  "cursor": null,
  "limit": 50,
  "include_aggregations": false
}
```

### Aggregation-only (dashboard, sidebar counts, Reports)

Set `size: 0` (legacy parity) or omit cursor/limit — returns buckets without row data:

```json
{
  "size": 0,
  "filters": {},
  "aggregations": ["meta.lead_status", "meta.lead_owner", "meta.lead_temp"]
}
```

If `aggregations` is omitted, the server returns the **default facet set** for that resource (see below).

### Response shape (ES-compatible adapter)

Row data plus aggregations in one payload when requested:

```json
{
  "hits": {
    "total": { "value": 1204 },
    "hits": [
      {
        "_source": {
          "id": 1,
          "post_title": "Smith Application",
          "meta": { "lead_status": "active", "lead_owner": "Jane Doe" }
        }
      }
    ]
  },
  "aggregations": {
    "meta.lead_status": {
      "buckets": [
        { "key": "active", "doc_count": 400 },
        { "key": "closed", "doc_count": 120 }
      ]
    },
    "meta.lead_owner": {
      "buckets": [
        { "key": "Jane Doe", "doc_count": 85 },
        { "key": "Not defined", "doc_count": 12 }
      ]
    }
  },
  "meta": {
    "next_cursor": "eyJpZCI6NTB9"
  }
}
```

**Why ES shape:** the grid UI reads `aggregations['meta.lead_status'].buckets` with `{ key, doc_count }`. FIL normalizes Postgres `GROUP BY` results into this structure so the frontend adapter stays thin.

**Storage vs API:** aggregation keys like `meta.lead_status` are **legacy response aliases**. Database storage uses real columns (`leads.lead_fdd_status`, `leads.owner_user_id`, etc.) per `docs/METADATA.md`.

A simplified alias is also accepted on the wire for new code:

```json
{
  "data": [ ... ],
  "meta": { "total": 1204, "next_cursor": "..." },
  "aggregations": { ... }
}
```

`GridQueryService` always emits **both** top-level `aggregations` and normalized `hits` when serving the SPA.

## Default aggregations per resource

Default facet sets per resource (legacy parity):

| Resource | Aggregation keys | Postgres column / join |
| -------- | ---------------- | ---------------------- |
| `leads` | `meta.lead_status` | `leads.lead_fdd_status` or `lead_status` |
| | `meta.lead_owner` | join `users` → display name |
| | `meta.likelihood_to_close` | `leads.likelihood_to_close` |
| | `meta.lead_temp` | `leads.lead_temp` |
| | `meta.lead_source` | `leads.lead_source` |
| `stores` | `meta.stores` | `stores.store_status` |
| `contacts` | `meta.contacts` | `roles.name` via spatie |
| default (areas, etc.) | `meta.area` | `areas.slug` |

Role scoping (`user_filters`, owner visibility) is applied in the **same WHERE clause** before `GROUP BY`, so facet counts match the rows the user can see.

## How aggregations are computed (Postgres)

No search cluster — standard SQL:

```sql
SELECT lead_status AS key, COUNT(*)::int AS doc_count
FROM leads
WHERE /* role scope + active filters except the facet being computed */
GROUP BY lead_status
ORDER BY doc_count DESC
LIMIT 100;
```

Implementation notes:

1. **Terms buckets** — `GROUP BY` on typed columns (most facets).
2. **Filtered aggregations** — when a sidebar filter is active, other facets re-run with that filter in WHERE (same as ES `post_filter` behavior).
3. **Numeric terms** — `likelihood_to_close` grouped as discrete values or binned ranges.
4. **Dynamic menus** — `meta.lead_owner` buckets drive `menuItemsWithDynData()` submenus.
5. **Parent/child counts** — composite menu slugs (e.g. “active leads”) sum child buckets in the frontend; backend returns flat terms buckets per field.
6. **Concurrent facet queries** — optional `DB::select` per aggregation field or one query with `GROUP BY GROUPING SETS` for hot paths.

## Performance

1. **Typed columns + B-tree indexes** on filter/sort/facet fields.
2. **Composite indexes** for common tab filters (e.g. `(lead_status, owner_user_id)`).
3. **`pg_trgm` GIN** on title/name/email for fuzzy search.
4. **Cursor pagination** for rows — aggregations never use deep `OFFSET`.
5. **Facet cache** — `Cache::remember("aggs.leads.{scopeHash}", 60, ...)` keyed by resource + role scope + filter hash; file/database cache (no Redis required).
6. **Aggregation-only requests** skip row hydration entirely (`size: 0`).
7. **Eager loading** on row queries only; aggregations use indexed columns, not JSONB scans.

## Frontend integration

- Grid queries: `POST /api/v1/query/leads`
- Aggregation-only calls use the same endpoint with `size: 0`
- Keep existing helpers that read `aggregations.*.buckets`
- AG Grid infinite row model unchanged; map `hits.hits[]._source` to cell renderers

## AI / RAG

Document search for the AI assistant stays in the **external AI service** (`FIL_AI_SERVICE_URL`). FIL does not index content for RAG locally.
