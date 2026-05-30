import { apiGet, apiPost } from './client';

export interface DocumentExportStartResponse {
  export_id: string;
  status: string;
}

export interface DocumentExportStatus {
  export_id: string;
  status: 'preparing' | 'processing' | 'completed' | 'error';
  message: string | null;
  progress: {
    current: number;
    total: number;
    percentage: number;
  } | null;
  zip_url: string | null;
  error: string | null;
}

export function startDocumentExport(payload: {
  entity: string;
  doc_type: string;
  doc_type_label: string;
}): Promise<DocumentExportStartResponse> {
  return apiPost<{ data: DocumentExportStartResponse }>('/v1/documents/export/start', payload).then(
    (body) => body.data,
  );
}

export function fetchDocumentExportStatus(exportId: string): Promise<DocumentExportStatus> {
  const params = new URLSearchParams({ export_id: exportId });

  return apiGet<{ data: DocumentExportStatus }>(`/v1/documents/export/status?${params.toString()}`).then(
    (body) => body.data,
  );
}

export async function pollDocumentExport(
  exportId: string,
  onUpdate: (status: DocumentExportStatus) => void,
  options?: { intervalMs?: number; timeoutMs?: number },
): Promise<DocumentExportStatus> {
  const intervalMs = options?.intervalMs ?? 1000;
  const timeoutMs = options?.timeoutMs ?? 300_000;
  const started = Date.now();

  return new Promise((resolve, reject) => {
    const poll = async () => {
      try {
        const status = await fetchDocumentExportStatus(exportId);
        onUpdate(status);

        if (status.status === 'completed') {
          resolve(status);

          return;
        }

        if (status.status === 'error') {
          reject(new Error(status.error ?? 'Export failed'));

          return;
        }

        if (Date.now() - started >= timeoutMs) {
          reject(new Error('Export timed out'));

          return;
        }

        window.setTimeout(() => {
          void poll();
        }, intervalMs);
      } catch (error: unknown) {
        reject(error instanceof Error ? error : new Error('Export failed'));
      }
    };

    void poll();
  });
}
