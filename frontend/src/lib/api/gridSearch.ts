import { apiPost } from './client';
import type { GridQueryPayload } from './grid';

export interface GridSearchNavigation {
  search?: string;
  filter?: string;
  subFilter?: string;
  leadtemp?: string;
  sortField?: string;
  sortDirection?: 'asc' | 'desc';
}

export interface GridSearchInterpretation {
  summary: string;
  confidence: 'low' | 'medium' | 'high' | string;
  source: 'ai' | 'heuristic' | 'none' | string;
  original_query: string;
  result_count: number;
  query: GridQueryPayload;
  navigation: GridSearchNavigation;
}

export function interpretGridSearch(
  resource: 'leads' | 'stores' | 'contacts',
  q: string,
): Promise<GridSearchInterpretation> {
  return apiPost<{ data: GridSearchInterpretation }>(`/v1/query/${resource}/interpret`, { q }).then(
    (response) => response.data,
  );
}
