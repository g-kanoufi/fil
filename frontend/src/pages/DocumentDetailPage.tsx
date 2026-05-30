import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { PageHeader } from '@/components/ui/PageHeader';
import { Card } from '@/components/ui/Card';
import { Alert } from '@/components/ui/Alert';
import { LoadingState } from '@/components/ui/LoadingState';
import { TextLink } from '@/components/ui/TextLink';
import { fetchDocument, type DocumentRecord } from '@/lib/api/documents';
import { DocumentDownloadLink } from '@/components/documents/DocumentDownloadLink';
import { textLink } from '@/lib/ui/tokens';

function formatDate(value: string): string {
  return new Date(value).toLocaleString();
}

export function DocumentDetailPage() {
  const { id } = useParams<{ id: string }>();
  const documentId = Number(id);
  const [document, setDocument] = useState<DocumentRecord | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!Number.isFinite(documentId)) {
      setError('Invalid document id');
      setLoading(false);

      return;
    }

    let cancelled = false;

    void fetchDocument(documentId)
      .then((data) => {
        if (!cancelled) {
          setDocument(data);
        }
      })
      .catch((loadError: unknown) => {
        if (!cancelled) {
          setError(loadError instanceof Error ? loadError.message : 'Failed to load document');
        }
      })
      .finally(() => {
        if (!cancelled) {
          setLoading(false);
        }
      });

    return () => {
      cancelled = true;
    };
  }, [documentId]);

  if (loading) {
    return <LoadingState label="Loading document…" />;
  }

  if (error || !document) {
    return (
      <>
        <PageHeader title="Document" />
        <Alert variant="error">{error ?? 'Document not found'}</Alert>
        <TextLink to="/documents" plain className="mt-4 inline-block text-sm">
          ← Back to documents
        </TextLink>
      </>
    );
  }

  return (
    <>
      <PageHeader title={document.title} description={`Document #${document.id}`} />

      <div className="mb-4">
        <TextLink to="/documents" plain className="text-sm">
          ← Back to documents
        </TextLink>
      </div>

      <Card>
        <dl className="grid gap-4 text-sm md:grid-cols-2">
          <div>
            <dt className="font-medium text-foreground">MIME type</dt>
            <dd className="mt-1 text-muted">{document.mime_type ?? '—'}</dd>
          </div>
          <div>
            <dt className="font-medium text-foreground">Status</dt>
            <dd className="mt-1 text-muted">{document.status}</dd>
          </div>
          <div>
            <dt className="font-medium text-foreground">Uploaded by</dt>
            <dd className="mt-1 text-muted">{document.uploader_name ?? '—'}</dd>
          </div>
          <div>
            <dt className="font-medium text-foreground">Updated</dt>
            <dd className="mt-1 text-muted">{formatDate(document.updated_at)}</dd>
          </div>
        </dl>

        <div className="mt-6">
          <DocumentDownloadLink documentId={document.id} className={textLink}>
            Download file
          </DocumentDownloadLink>
        </div>
      </Card>
    </>
  );
}
