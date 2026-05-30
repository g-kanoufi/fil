import type { ColDef } from 'ag-grid-community';
import { formatGridDate } from './cellFormatters';

type GridResource = 'leads' | 'stores' | 'contacts';

function metaValue(field: string) {
  return (params: { data?: { _source?: Record<string, unknown> } }) => {
    const meta = params.data?._source?.meta as Record<string, unknown> | undefined;
    return meta?.[field] ?? '—';
  };
}

const BASE_COLUMNS: Record<GridResource, ColDef[]> = {
  leads: [
    { headerName: 'ID', field: 'id', width: 90, valueGetter: (p) => p.data?._source?.id, sortable: true },
    {
      headerName: 'Name',
      field: 'title',
      flex: 1,
      minWidth: 200,
      valueGetter: (p) => p.data?._source?.post_title ?? '—',
      sortable: true,
    },
    { headerName: 'Status', field: 'lead_status', width: 180, valueGetter: metaValue('lead_status'), sortable: false },
    { headerName: 'Stage', field: 'lead_stage', width: 120, valueGetter: metaValue('lead_stage'), sortable: false },
    { headerName: 'Pipeline', field: 'pipeline_phase', width: 140, valueGetter: metaValue('pipeline_phase'), sortable: true },
    { headerName: 'Owner', field: 'lead_owner', width: 160, valueGetter: metaValue('lead_owner'), sortable: false },
    { headerName: 'Temp', field: 'lead_temp', width: 100, valueGetter: metaValue('lead_temp'), sortable: false },
    { headerName: 'Source', field: 'lead_source', width: 140, valueGetter: metaValue('lead_source'), sortable: false },
    { headerName: 'Likelihood', field: 'likelihood_to_close', width: 110, valueGetter: metaValue('likelihood_to_close'), sortable: false },
    {
      headerName: 'Updated',
      field: 'updated_at',
      width: 130,
      valueGetter: (p) => formatGridDate(p.data?._source?.updated_at),
      sortable: true,
    },
  ],
  stores: [
    { headerName: 'ID', field: 'id', width: 90, valueGetter: (p) => p.data?._source?.id, sortable: true },
    {
      headerName: 'Name',
      field: 'name',
      flex: 1,
      minWidth: 200,
      valueGetter: (p) => p.data?._source?.post_title ?? '—',
      sortable: true,
    },
    { headerName: 'Status', field: 'store_status', width: 140, valueGetter: metaValue('store_status'), sortable: false },
    { headerName: 'Area', field: 'areas', width: 160, valueGetter: metaValue('areas'), sortable: false },
    {
      headerName: 'Updated',
      field: 'updated_at',
      width: 130,
      valueGetter: (p) => formatGridDate(p.data?._source?.updated_at),
      sortable: true,
    },
  ],
  contacts: [
    { headerName: 'ID', field: 'id', width: 90, valueGetter: (p) => p.data?._source?.id, sortable: true },
    {
      headerName: 'Name',
      field: 'name',
      flex: 1,
      minWidth: 200,
      valueGetter: (p) => p.data?._source?.post_title ?? '—',
      sortable: true,
    },
    { headerName: 'Role', field: 'contacts', width: 140, valueGetter: metaValue('contacts'), sortable: false },
    {
      headerName: 'Email',
      field: 'email',
      flex: 1,
      minWidth: 220,
      valueGetter: metaValue('email'),
      sortable: true,
    },
    {
      headerName: 'Updated',
      field: 'updated_at',
      width: 130,
      valueGetter: (p) => formatGridDate(p.data?._source?.updated_at),
      sortable: true,
    },
  ],
};

const FILTER_COLUMN_FIELDS: Record<GridResource, Record<string, string[]>> = {
  leads: {
    lead_status: ['id', 'title', 'lead_status', 'pipeline_phase', 'lead_owner', 'updated_at'],
    lead_owner: ['id', 'title', 'lead_status', 'lead_owner', 'lead_temp', 'updated_at'],
    lead_temp: ['id', 'title', 'lead_temp', 'lead_status', 'lead_owner', 'updated_at'],
    lead_source: ['id', 'title', 'lead_source', 'lead_status', 'lead_owner', 'updated_at'],
  },
  stores: {
    store_status: ['id', 'name', 'store_status', 'areas', 'updated_at'],
    store_area: ['id', 'name', 'store_status', 'areas', 'updated_at'],
  },
  contacts: {
    contact_role: ['id', 'name', 'contacts', 'email', 'updated_at'],
  },
};

export function gridColumnsFor(
  resource: GridResource,
  filter = '',
): ColDef[] {
  const all = BASE_COLUMNS[resource];
  const fieldSet = filter ? FILTER_COLUMN_FIELDS[resource][filter] : null;

  if (!fieldSet) {
    return all;
  }

  const byField = new Map(all.map((col) => [col.field, col]));
  return fieldSet.map((field) => byField.get(field)).filter((col): col is ColDef => Boolean(col));
}

export function apiSortField(_resource: GridResource, colField: string): string | null {
  const map: Record<string, string> = {
    title: 'title',
    name: 'name',
    email: 'email',
    updated_at: 'updated_at',
    pipeline_phase: 'pipeline_phase',
  };

  return map[colField] ?? null;
}
