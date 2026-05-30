import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/Button';
import {
  pollDocumentExport,
  startDocumentExport,
  type DocumentExportStatus,
} from '@/lib/api/documentExport';

interface DocumentExportButtonProps {
  entity: string;
  docType: string;
  docTypeLabel: string;
  fileCount: number;
}

export function DocumentExportButton({
  entity,
  docType,
  docTypeLabel,
  fileCount,
}: DocumentExportButtonProps) {
  const [state, setState] = useState<'idle' | 'loading' | 'success' | 'error'>('idle');
  const [message, setMessage] = useState('');
  const [progress, setProgress] = useState<DocumentExportStatus['progress']>(null);
  const cancelledRef = useRef(false);

  useEffect(() => {
    cancelledRef.current = false;

    return () => {
      cancelledRef.current = true;
    };
  }, []);

  const handleExport = async () => {
    setState('loading');
    setMessage('Preparing export…');
    setProgress(null);

    try {
      const { export_id: exportId } = await startDocumentExport({
        entity,
        doc_type: docType,
        doc_type_label: docTypeLabel,
      });

      const finalStatus = await pollDocumentExport(exportId, (status) => {
        if (cancelledRef.current) {
          return;
        }

        setMessage(status.message ?? 'Exporting…');
        setProgress(status.progress);
      });

      if (cancelledRef.current) {
        return;
      }

      if (finalStatus.zip_url) {
        window.location.assign(finalStatus.zip_url);
      }

      setState('success');
      setMessage('Download started');
      window.setTimeout(() => {
        if (!cancelledRef.current) {
          setState('idle');
          setMessage('');
          setProgress(null);
        }
      }, 3000);
    } catch (exportError: unknown) {
      if (cancelledRef.current) {
        return;
      }

      setState('error');
      setMessage(exportError instanceof Error ? exportError.message : 'Export failed');
      window.setTimeout(() => {
        if (!cancelledRef.current) {
          setState('idle');
          setMessage('');
          setProgress(null);
        }
      }, 5000);
    }
  };

  const label =
    state === 'loading'
      ? progress && progress.total > 0
        ? `Exporting ${progress.percentage}%`
        : 'Exporting…'
      : state === 'success'
        ? 'Ready'
        : state === 'error'
          ? 'Export failed'
          : 'Download ZIP';

  return (
    <div className="mb-3 flex flex-wrap items-center gap-3">
      <Button
        size="sm"
        variant="secondary"
        disabled={state === 'loading' || fileCount === 0}
        onClick={() => void handleExport()}
      >
        {label}
      </Button>
      {message ? <span className="text-sm text-muted">{message}</span> : null}
    </div>
  );
}
