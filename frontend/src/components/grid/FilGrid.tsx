import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { AgGridReact } from 'ag-grid-react';
import type {
  BodyScrollEndEvent,
  BodyScrollEvent,
  ColDef,
  GridApi,
  RowClickedEvent,
  SelectionChangedEvent,
  SortChangedEvent,
} from 'ag-grid-community';
import { Alert } from '@/components/ui/Alert';
import { EmptyState } from '@/components/ui/EmptyState';
import { LoadingState } from '@/components/ui/LoadingState';
import { queryGrid, type GridQueryPayload, type GridQueryResponse } from '@/lib/api/grid';
import { apiSortField } from '@/lib/grid/columns';
import { getFilGridTheme } from '@/lib/grid/agGridSetup';
import { useTheme } from '@/providers/ThemeProvider';

export interface GridQueryState {
  search?: string;
  filters?: Record<string, unknown>;
  sort?: Array<{ field: string; direction: 'asc' | 'desc' }>;
}

interface FilGridProps {
  resource: 'leads' | 'stores' | 'contacts';
  columnDefs: ColDef[];
  queryState: GridQueryState;
  queryKey: string;
  onRowNavigate?: (id: number) => void;
  onAggregations?: (aggregations: GridQueryResponse['aggregations']) => void;
  onTotalChange?: (total: number) => void;
  onSortChange?: (field: string, direction: 'asc' | 'desc') => void;
  pageSize?: number;
  enableSelection?: boolean;
  onSelectionChange?: (ids: number[]) => void;
}

type GridRow = GridQueryResponse['hits']['hits'][number];

const EMPTY_COPY: Record<FilGridProps['resource'], { title: string; description: string }> = {
  leads: {
    title: 'No leads found',
    description: 'Try clearing filters or adjusting your search. New applications from the widget will appear here.',
  },
  stores: {
    title: 'No stores found',
    description: 'Try clearing filters or add a new store from the toolbar.',
  },
  contacts: {
    title: 'No contacts found',
    description: 'Try clearing filters or search for a different name or email.',
  },
};

/** Start loading the next page when the viewport is this many rows from the end. */
const PREFETCH_ROW_THRESHOLD = 20;

export function FilGrid({
  resource,
  columnDefs,
  queryState,
  queryKey,
  onRowNavigate,
  onAggregations,
  onTotalChange,
  onSortChange,
  pageSize = 50,
  enableSelection = false,
  onSelectionChange,
}: FilGridProps) {
  const { resolved } = useTheme();
  const gridTheme = useMemo(() => getFilGridTheme(resolved === 'dark'), [resolved]);
  const gridRef = useRef<AgGridReact>(null);
  const cursorRef = useRef<string | null>(null);
  const loadingMoreRef = useRef(false);
  const payloadRef = useRef<Omit<GridQueryPayload, 'limit' | 'cursor'>>({
    sort: queryState.sort,
  });
  const [rows, setRows] = useState<GridRow[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const payloadBase = useMemo<Omit<GridQueryPayload, 'limit' | 'cursor'>>(
    () => ({
      search: queryState.search?.trim() || undefined,
      filters: queryState.filters,
      sort: queryState.sort,
    }),
    [queryState],
  );

  payloadRef.current = payloadBase;

  useEffect(() => {
    if (!enableSelection) {
      return;
    }

    const api = gridRef.current?.api;

    if (api) {
      api.deselectAll();
      onSelectionChange?.([]);
    }
  }, [queryKey, enableSelection, onSelectionChange]);

  const loadPage = useCallback(
    async (nextCursor: string | null, replace: boolean) => {
      if (replace) {
        setLoading(true);
        setError(null);
        cursorRef.current = null;
      } else {
        if (loadingMoreRef.current || !nextCursor) {
          return;
        }

        loadingMoreRef.current = true;
        setLoadingMore(true);
      }

      try {
        const response = await queryGrid(resource, {
          ...payloadRef.current,
          limit: pageSize,
          cursor: nextCursor,
          include_aggregations: replace,
        });

        setTotal(response.hits.total.value);
        onTotalChange?.(response.hits.total.value);

        if (replace && response.aggregations) {
          onAggregations?.(response.aggregations);
        }

        cursorRef.current = response.meta.next_cursor;
        setRows((current) => (replace ? response.hits.hits : [...current, ...response.hits.hits]));
      } catch (loadError: unknown) {
        if (!replace) {
          cursorRef.current = nextCursor;
        }
        setError(loadError instanceof Error ? loadError.message : 'Failed to load grid');
      } finally {
        loadingMoreRef.current = false;
        setLoading(false);
        setLoadingMore(false);
      }
    },
    [onAggregations, onTotalChange, pageSize, resource],
  );

  useEffect(() => {
    void loadPage(null, true);
  }, [loadPage, queryKey]);

  const maybeLoadMore = useCallback(
    (api: GridApi) => {
      const nextCursor = cursorRef.current;

      if (!nextCursor || loadingMoreRef.current) {
        return;
      }

      const loadedCount = api.getDisplayedRowCount();

      if (loadedCount === 0) {
        return;
      }

      const lastDisplayed = api.getLastDisplayedRowIndex();

      if (lastDisplayed >= loadedCount - PREFETCH_ROW_THRESHOLD) {
        void loadPage(nextCursor, false);
      }
    },
    [loadPage],
  );

  const onBodyScroll = useCallback(
    (event: BodyScrollEvent) => {
      if (event.direction !== 'vertical') {
        return;
      }

      maybeLoadMore(event.api);
    },
    [maybeLoadMore],
  );

  const onBodyScrollEnd = useCallback(
    (event: BodyScrollEndEvent) => {
      maybeLoadMore(event.api);
    },
    [maybeLoadMore],
  );

  const defaultColDef = useMemo<ColDef>(
    () => ({
      sortable: true,
      resizable: true,
      suppressMovable: true,
      unSortIcon: true,
    }),
    [],
  );

  const selectionColumn = useMemo<ColDef>(
    () => ({
      colId: '__select',
      headerCheckboxSelection: true,
      checkboxSelection: true,
      width: 48,
      maxWidth: 48,
      pinned: 'left',
      suppressMovable: true,
      sortable: false,
      resizable: false,
    }),
    [],
  );

  const effectiveColumnDefs = useMemo(
    () => (enableSelection ? [selectionColumn, ...columnDefs] : columnDefs),
    [columnDefs, enableSelection, selectionColumn],
  );

  const onSortChanged = useCallback(
    (event: SortChangedEvent) => {
      if (!onSortChange) {
        return;
      }

      const sorted = event.api.getColumnState().find((col) => col.sort);
      if (!sorted?.colId) {
        return;
      }

      const apiField = apiSortField(resource, sorted.colId);
      if (!apiField) {
        return;
      }

      onSortChange(apiField, sorted.sort === 'asc' ? 'asc' : 'desc');
    },
    [onSortChange, resource],
  );

  const getRowId = useCallback(
    (params: { data: GridRow }) => String(params.data._source?.id ?? ''),
    [],
  );

  const onSelectionChanged = useCallback(
    (event: SelectionChangedEvent) => {
      if (!onSelectionChange) {
        return;
      }

      const selected = event.api.getSelectedRows() as GridRow[];
      const ids = selected
        .map((row) => Number(row._source?.id))
        .filter((id) => Number.isFinite(id) && id > 0);

      onSelectionChange(ids);
    },
    [onSelectionChange],
  );

  const onRowClicked = useCallback(
    (event: RowClickedEvent) => {
      if (!onRowNavigate || !event.data) {
        return;
      }

      const id = Number((event.data as { _source?: { id?: unknown } })._source?.id);

      if (Number.isFinite(id)) {
        onRowNavigate(id);
      }
    },
    [onRowNavigate],
  );

  if (loading && rows.length === 0) {
    return <LoadingState label="Loading records…" />;
  }

  const empty = EMPTY_COPY[resource];

  if (!loading && rows.length === 0 && !error) {
    return (
      <div className="fil-grid-wrap">
        <EmptyState title={empty.title} description={empty.description} />
      </div>
    );
  }

  return (
    <div className="fil-grid-wrap">
      {error ? <Alert variant="error" className="mb-4">{error}</Alert> : null}

      <div className="mb-3 flex items-center justify-between text-sm text-muted">
        <span>
          {rows.length.toLocaleString()} loaded
          {total > rows.length ? ` of ${total.toLocaleString()}` : ''}
        </span>
        {loadingMore ? <span>Loading more…</span> : null}
      </div>

      <div
        className="w-full overflow-hidden rounded-xl border border-border bg-surface"
        style={{ height: 'min(70vh, 720px)' }}
      >
        <AgGridReact
          ref={gridRef}
          theme={gridTheme}
          rowData={rows}
          columnDefs={effectiveColumnDefs}
          defaultColDef={defaultColDef}
          rowHeight={44}
          headerHeight={42}
          rowBuffer={12}
          animateRows
          suppressCellFocus
          suppressScrollOnNewData
          suppressRowClickSelection={enableSelection}
          domLayout="normal"
          getRowId={getRowId}
          onBodyScroll={onBodyScroll}
          onBodyScrollEnd={onBodyScrollEnd}
          onSortChanged={onSortChanged}
          onSelectionChanged={enableSelection ? onSelectionChanged : undefined}
          onRowClicked={onRowNavigate ? onRowClicked : undefined}
          rowClass={onRowNavigate ? 'cursor-pointer' : undefined}
          rowSelection={enableSelection ? { mode: 'multiRow', checkboxes: true } : undefined}
          style={{ height: '100%', width: '100%' }}
        />
      </div>
    </div>
  );
}
