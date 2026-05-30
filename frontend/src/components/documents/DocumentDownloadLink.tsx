import { useState, type ReactNode } from 'react';
import { downloadDocument } from '@/lib/api/documents';

interface DocumentDownloadLinkProps {
  documentId: number;
  filename?: string;
  className?: string;
  children: ReactNode;
}

export function DocumentDownloadLink({
  documentId,
  filename,
  className,
  children,
}: DocumentDownloadLinkProps) {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  return (
    <span className="inline-flex flex-col items-start gap-1">
      <button
        type="button"
        className={className}
        disabled={loading}
        onClick={() => {
          setLoading(true);
          setError(null);
          void downloadDocument(documentId, filename)
            .catch((downloadError: unknown) => {
              setError(
                downloadError instanceof Error ? downloadError.message : 'Download failed',
              );
            })
            .finally(() => {
              setLoading(false);
            });
        }}
      >
        {loading ? 'Opening…' : children}
      </button>
      {error ? <span className="text-xs text-red-600">{error}</span> : null}
    </span>
  );
}
