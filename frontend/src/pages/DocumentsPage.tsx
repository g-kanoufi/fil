import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { PageHeader } from '@/components/ui/PageHeader';
import { Card } from '@/components/ui/Card';
import { Alert } from '@/components/ui/Alert';
import { LoadingState } from '@/components/ui/LoadingState';
import { Button } from '@/components/ui/Button';
import { DataTable } from '@/components/ui/DataTable';
import { FormField } from '@/components/ui/FormField';
import { fetchDocuments, type DocumentRecord } from '@/lib/api/documents';
import {
  fetchAllDocumentBrowserRows,
  fetchDocumentsBrowserSettings,
  type DocumentBrowserRow,
  type DocumentsBrowserSettings,
} from '@/lib/api/documentsBrowser';
import { DocumentExportButton } from '@/components/documents/DocumentExportButton';
import { DocumentDownloadLink } from '@/components/documents/DocumentDownloadLink';

type TabId = 'library' | 'browse';

const ENTITY_LABELS: Record<string, string> = {
  store: 'Stores',
  lead: 'Applications',
  user: 'Users',
  location: 'Locations',
};

function formatDate(value: string): string {
  return new Date(value).toLocaleString();
}

function formatSize(bytes: number | null): string {
  if (!bytes) {
    return '—';
  }

  if (bytes < 1024) {
    return `${bytes} B`;
  }

  return `${(bytes / 1024).toFixed(1)} KB`;
}

export function DocumentsPage() {
  const [tab, setTab] = useState<TabId>('browse');
  const [documents, setDocuments] = useState<DocumentRecord[]>([]);
  const [browserSettings, setBrowserSettings] = useState<DocumentsBrowserSettings | null>(null);
  const [browserRows, setBrowserRows] = useState<DocumentBrowserRow[]>([]);
  const [entity, setEntity] = useState('store');
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const loadLibrary = useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      setDocuments(await fetchDocuments(search));
    } catch (loadError: unknown) {
      setError(loadError instanceof Error ? loadError.message : 'Failed to load documents');
    } finally {
      setLoading(false);
    }
  }, [search]);

  const loadBrowse = useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      const [settings, rows] = await Promise.all([
        fetchDocumentsBrowserSettings(),
        fetchAllDocumentBrowserRows(entity),
      ]);

      setBrowserSettings(settings);
      setBrowserRows(rows);
    } catch (loadError: unknown) {
      setError(loadError instanceof Error ? loadError.message : 'Failed to load document browser');
    } finally {
      setLoading(false);
    }
  }, [entity]);

  useEffect(() => {
    if (tab === 'library') {
      void loadLibrary();
    } else {
      void loadBrowse();
    }
  }, [tab, loadLibrary, loadBrowse]);

  const groupedRows = useMemo(() => {
    const groups = new Map<string, DocumentBrowserRow[]>();

    for (const row of browserRows) {
      const key = row.doc_type_label;
      const bucket = groups.get(key) ?? [];
      bucket.push(row);
      groups.set(key, bucket);
    }

    return [...groups.entries()];
  }, [browserRows]);

  return (
    <>
      <PageHeader
        title="Documents"
        description="Browse franchise files by entity or search the full document library."
      />

      <div className="mb-4 flex gap-2">
        <Button
          size="sm"
          variant={tab === 'browse' ? 'primary' : 'secondary'}
          onClick={() => setTab('browse')}
        >
          By entity
        </Button>
        <Button
          size="sm"
          variant={tab === 'library' ? 'primary' : 'secondary'}
          onClick={() => setTab('library')}
        >
          Library
        </Button>
      </div>

      {error ? <Alert variant="error" className="mb-4">{error}</Alert> : null}

      {tab === 'library' ? (
        <>
          <Card className="mb-4">
            <div className="flex flex-wrap items-end gap-3">
              <FormField
                label="Search"
                id="documents-search"
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder="Title"
              />
              <Button size="sm" variant="secondary" onClick={() => void loadLibrary()} disabled={loading}>
                Apply
              </Button>
            </div>
            <p className="mt-3 text-sm text-muted">{documents.length.toLocaleString()} document(s)</p>
          </Card>

          {loading ? <LoadingState label="Loading documents…" /> : null}

          {!loading ? (
            <DataTable
              rows={documents}
              rowKey={(row) => row.id}
              emptyMessage="No documents found."
              columns={[
                {
                  key: 'title',
                  header: 'Title',
                  render: (row) => (
                    <Link to={`/documents/${row.id}`} className="font-medium text-link hover:underline">
                      {row.title}
                    </Link>
                  ),
                },
                {
                  key: 'type',
                  header: 'Type',
                  render: (row) => row.mime_type ?? '—',
                },
                {
                  key: 'size',
                  header: 'Size',
                  render: (row) => formatSize(row.file_size),
                },
                {
                  key: 'status',
                  header: 'Status',
                  render: (row) => row.status,
                },
                {
                  key: 'updated',
                  header: 'Updated',
                  render: (row) => formatDate(row.updated_at),
                },
                {
                  key: 'actions',
                  header: '',
                  render: (row) => (
                    <DocumentDownloadLink
                      documentId={row.id}
                      className="text-sm text-link hover:underline"
                    >
                      Download
                    </DocumentDownloadLink>
                  ),
                },
              ]}
            />
          ) : null}
        </>
      ) : (
        <>
          <Card className="mb-4">
            <div className="flex flex-wrap items-end gap-3">
              <label className="flex min-w-[12rem] flex-col gap-1 text-sm">
                <span className="font-medium text-foreground">Entity</span>
                <select
                  className="rounded-lg border border-border bg-surface px-3 py-2"
                  value={entity}
                  onChange={(event) => setEntity(event.target.value)}
                >
                  {(browserSettings?.enabled_entities ?? ['store', 'lead', 'user']).map((value) => (
                    <option key={value} value={value}>
                      {ENTITY_LABELS[value] ?? value}
                    </option>
                  ))}
                </select>
              </label>
              <Button size="sm" variant="secondary" onClick={() => void loadBrowse()} disabled={loading}>
                Refresh
              </Button>
            </div>
            <p className="mt-3 text-sm text-muted">
              {browserRows.length.toLocaleString()} file(s) in {ENTITY_LABELS[entity] ?? entity}
            </p>
          </Card>

          {loading ? <LoadingState label="Loading document browser…" /> : null}

          {!loading && groupedRows.length === 0 ? (
            <Card>
              <p className="text-sm text-muted">No imported files for this entity yet. Run `legacy:import --only=documents` after stores/users/leads.</p>
            </Card>
          ) : null}

          {!loading
            ? groupedRows.map(([docType, rows]) => (
                <Card key={docType} className="mb-4">
                  <div className="flex flex-wrap items-start justify-between gap-3">
                    <h2 className="text-base font-semibold text-foreground">{docType}</h2>
                    <DocumentExportButton
                      entity={entity}
                      docType={rows[0]?.doc_type ?? docType}
                      docTypeLabel={docType}
                      fileCount={rows.length}
                    />
                  </div>
                  <DataTable
                    rows={rows}
                    rowKey={(row) => row.id}
                    emptyMessage="No files."
                    columns={[
                      {
                        key: 'entity',
                        header: ENTITY_LABELS[entity] ?? 'Entity',
                        render: (row) => row.entity_title ?? `#${row.entity_id}`,
                      },
                      {
                        key: 'title',
                        header: 'File',
                        render: (row) =>
                          row.document_id ? (
                            <Link
                              to={`/documents/${row.document_id}`}
                              className="text-link hover:underline"
                            >
                              {row.title ?? row.file_name ?? 'Document'}
                            </Link>
                          ) : (
                            row.title ?? '—'
                          ),
                      },
                      {
                        key: 'mime',
                        header: 'Type',
                        render: (row) => row.mime_type ?? '—',
                      },
                      {
                        key: 'actions',
                        header: '',
                        render: (row) =>
                          row.document_id ? (
                            <DocumentDownloadLink
                              documentId={row.document_id}
                              className="text-sm text-link hover:underline"
                            >
                              Open
                            </DocumentDownloadLink>
                          ) : (
                            '—'
                          ),
                      },
                    ]}
                  />
                </Card>
              ))
            : null}
        </>
      )}
    </>
  );
}
