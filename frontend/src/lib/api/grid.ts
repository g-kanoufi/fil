import { apiPost } from './client';

export interface GridBucket {
  key: string | number | null;
  doc_count: number;
}

export interface GridQueryPayload {
  search?: string;
  filters?: Record<string, unknown>;
  sort?: Array<{ field: string; direction: 'asc' | 'desc' }>;
  cursor?: string | null;
  limit?: number;
  size?: number;
  include_aggregations?: boolean;
  aggregations?: string[];
}

export interface GridQueryResponse {
  hits: {
    total: { value: number };
    hits: Array<{ _source: Record<string, unknown> }>;
  };
  aggregations?: Record<string, { buckets: GridBucket[] }>;
  meta: { next_cursor: string | null };
}

export function queryGrid(
  resource: 'leads' | 'stores' | 'contacts',
  payload: GridQueryPayload,
): Promise<GridQueryResponse> {
  return apiPost<GridQueryResponse>(`/v1/query/${resource}`, payload);
}
