import { useState } from 'react';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { bulkSendFdd, type BulkSendResult } from '@/lib/api/fdds';

interface FddBulkSendModalProps {
  open: boolean;
  leadIds: number[];
  onClose: () => void;
  onComplete?: (result: BulkSendResult) => void;
}

export function FddBulkSendModal({ open, leadIds, onClose, onComplete }: FddBulkSendModalProps) {
  const [sendingType, setSendingType] = useState<'unit' | 'area' | null>(null);
  const [status, setStatus] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  if (!open) {
    return null;
  }

  async function onSend(type: 'unit' | 'area') {
    if (leadIds.length === 0) {
      return;
    }

    setSendingType(type);
    setStatus(null);
    setError(null);

    try {
      const result = await bulkSendFdd(leadIds, type);
      const sentCount = result.sent.length;
      const skippedCount = result.skipped.length;

      setStatus(
        sentCount > 0
          ? `Sent ${type} FDD to ${sentCount} lead${sentCount === 1 ? '' : 's'}${
              skippedCount > 0 ? ` (${skippedCount} skipped)` : ''
            }.`
          : `No ${type} FDDs sent. ${skippedCount} lead${skippedCount === 1 ? '' : 's'} skipped.`,
      );

      onComplete?.(result);
    } catch (sendError: unknown) {
      setError(sendError instanceof Error ? sendError.message : 'Send failed');
    } finally {
      setSendingType(null);
    }
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
      <div
        className="w-full max-w-md rounded-xl border border-border bg-surface p-6 shadow-[var(--shadow-card)]"
        role="dialog"
        aria-labelledby="fdd-bulk-send-title"
      >
        <h2 id="fdd-bulk-send-title" className="text-lg font-semibold text-foreground">
          Send FDD to selected leads
        </h2>
        <p className="mt-2 text-sm text-muted">
          {leadIds.length.toLocaleString()} lead{leadIds.length === 1 ? '' : 's'} selected. Choose
          disclosure type to send.
        </p>

        {error ? (
          <Alert variant="error" className="mt-4">
            {error}
          </Alert>
        ) : null}
        {status ? (
          <Alert variant={status.includes('No ') ? 'error' : 'success'} className="mt-4">
            {status}
          </Alert>
        ) : null}

        <div className="mt-6 flex flex-wrap gap-2">
          <Button
            type="button"
            disabled={sendingType !== null}
            onClick={() => void onSend('unit')}
          >
            {sendingType === 'unit' ? 'Sending…' : 'Send unit FDD'}
          </Button>
          <Button
            type="button"
            variant="secondary"
            disabled={sendingType !== null}
            onClick={() => void onSend('area')}
          >
            {sendingType === 'area' ? 'Sending…' : 'Send area FDD'}
          </Button>
          <Button type="button" variant="secondary" onClick={onClose}>
            {status ? 'Close' : 'Cancel'}
          </Button>
        </div>
      </div>
    </div>
  );
}

interface FddBulkSendBarProps {
  selectedCount: number;
  onSend: () => void;
  onClear: () => void;
}

export function FddBulkSendBar({ selectedCount, onSend, onClear }: FddBulkSendBarProps) {
  if (selectedCount === 0) {
    return null;
  }

  return (
    <div className="mb-4 flex flex-wrap items-center gap-3 rounded-xl border border-accent-soft-border bg-accent-soft px-4 py-3">
      <span className="text-sm font-medium text-accent-soft-fg">
        {selectedCount.toLocaleString()} lead{selectedCount === 1 ? '' : 's'} selected
      </span>
      <Button type="button" size="sm" onClick={onSend}>
        Send FDD…
      </Button>
      <button
        type="button"
        className="text-sm text-accent-soft-muted underline-offset-2 hover:underline"
        onClick={onClear}
      >
        Clear selection
      </button>
    </div>
  );
}
