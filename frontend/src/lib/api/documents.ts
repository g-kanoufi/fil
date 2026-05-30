import { ApiError, apiGet } from './client';

export interface DocumentRecord {
  id: number;
  title: string;
  slug: string | null;
  mime_type: string | null;
  file_size: number | null;
  status: string;
  version: number | null;
  uploaded_by_user_id: number | null;
  uploader_name?: string | null;
  download_url: string;
  created_at: string;
  updated_at: string;
}

export function documentDownloadUrl(documentId: number): string {
  return `/documents/${documentId}/download`;
}

export async function downloadDocument(documentId: number, filename?: string): Promise<void> {
  const baseUrl = import.meta.env.VITE_API_URL ?? '/api';
  const response = await fetch(`${baseUrl}/v1/documents/${documentId}/download`, {
    credentials: 'include',
    headers: {
      Accept: 'application/octet-stream,application/pdf,*/*',
    },
  });

  if (!response.ok) {
    throw new ApiError(response.status, `Document download failed: ${response.status}`);
  }

  const blob = await response.blob();
  const disposition = response.headers.get('Content-Disposition') ?? '';
  const headerFilename = /filename="([^"]+)"/.exec(disposition)?.[1];
  const resolvedFilename = filename ?? headerFilename ?? `document-${documentId}`;
  const objectUrl = URL.createObjectURL(blob);

  if (blob.type === 'application/pdf' || resolvedFilename.endsWith('.pdf')) {
    window.open(objectUrl, '_blank', 'noopener,noreferrer');
  } else {
    const anchor = window.document.createElement('a');
    anchor.href = objectUrl;
    anchor.download = resolvedFilename;
    anchor.rel = 'noopener';
    anchor.click();
  }

  window.setTimeout(() => {
    URL.revokeObjectURL(objectUrl);
  }, 60_000);
}

export function fetchDocuments(search = '', status = ''): Promise<DocumentRecord[]> {
  const params = new URLSearchParams();

  if (search.trim()) {
    params.set('search', search.trim());
  }

  if (status.trim()) {
    params.set('status', status.trim());
  }

  const query = params.toString();

  return apiGet<{ data: DocumentRecord[] }>(`/v1/documents${query ? `?${query}` : ''}`).then(
    (body) => body.data,
  );
}

export function fetchDocument(id: number): Promise<DocumentRecord> {
  return apiGet<{ data: DocumentRecord }>(`/v1/documents/${id}`).then((body) => body.data);
}
