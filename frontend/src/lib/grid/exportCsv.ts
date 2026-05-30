import type { ColDef } from 'ag-grid-community';
import type { GridQueryResponse } from '@/lib/api/grid';
import { queryGrid, type GridQueryPayload } from '@/lib/api/grid';
import { buildCsv, downloadCsv } from '@/lib/export/csv';

type GridRow = GridQueryResponse['hits']['hits'][number];

function cellValue(row: GridRow, col: ColDef): string {
  if (col.valueGetter && typeof col.valueGetter === 'function') {
    const value = col.valueGetter({ data: row } as never);
    return value == null ? '' : String(value);
  }

  return '';
}

export async function exportGridCsv(
  resource: 'leads' | 'stores' | 'contacts',
  columns: ColDef[],
  payload: Omit<GridQueryPayload, 'limit' | 'cursor'>,
  filename: string,
): Promise<void> {
  const rows: GridRow[] = [];
  let cursor: string | null = null;

  do {
    const response = await queryGrid(resource, {
      ...payload,
      limit: 200,
      cursor,
      include_aggregations: false,
    });

    rows.push(...response.hits.hits);
    cursor = response.meta.next_cursor;
  } while (cursor);

  const headers = columns.map((col) => col.headerName ?? 'Column');
  const csvRows = rows.map((row) => columns.map((col) => cellValue(row, col)));

  downloadCsv(buildCsv(headers, csvRows), filename);
}
