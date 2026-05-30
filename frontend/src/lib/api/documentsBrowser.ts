import { apiGet } from './client';

export interface DocumentsBrowserSettings {
  enabled: boolean;
  enabled_entities: string[];
  document_types: string[];
  labels: Record<string, string>;
}

export interface DocumentBrowserRow {
  id: number;
  document_id: number | null;
  entity: string;
  entity_id: number;
  entity_title: string | null;
  doc_type: string;
  doc_type_label: string;
  title: string | null;
  file_name: string | null;
  mime_type: string | null;
  preview_url?: string | null;
  download_url: string | null;
  sort_order: number;
}

export interface DocumentBrowserRowsResponse {
  data: DocumentBrowserRow[];
  meta: {
    next_cursor: number | null;
    has_more: boolean;
  };
}

export function fetchDocumentsBrowserSettings(): Promise<DocumentsBrowserSettings> {
  return apiGet<{ data: DocumentsBrowserSettings }>('/v1/documents/settings').then((body) => body.data);
}

export function fetchDocumentBrowserRows(
  entity: string,
  cursor = 0,
): Promise<DocumentBrowserRowsResponse> {
  const params = new URLSearchParams({ entity, per_page: '50' });

  if (cursor > 0) {
    params.set('cursor', String(cursor));
  }

  return apiGet<DocumentBrowserRowsResponse>(`/v1/documents/rows?${params.toString()}`);
}

export async function fetchAllDocumentBrowserRows(entity: string): Promise<DocumentBrowserRow[]> {
  const rows: DocumentBrowserRow[] = [];
  let cursor = 0;

  while (true) {
    const response = await fetchDocumentBrowserRows(entity, cursor);
    rows.push(...response.data);

    if (!response.meta.has_more || response.meta.next_cursor === null) {
      break;
    }

    cursor = response.meta.next_cursor;
  }

  return rows;
}

export type { StoreAchEnrollment } from './ach';
export { createPlaidLinkToken, fetchStoreAchCustomer } from './ach';
