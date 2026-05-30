import { FormEvent, useEffect, useState } from 'react';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { FormField } from '@/components/ui/FormField';
import { fetchAreas, type Area } from '@/lib/api/areas';
import { createStore } from '@/lib/api/stores';

interface CreateStoreModalProps {
  open: boolean;
  onClose: () => void;
  onCreated: (storeId: number) => void;
}

export function CreateStoreModal({ open, onClose, onCreated }: CreateStoreModalProps) {
  const [name, setName] = useState('');
  const [areaId, setAreaId] = useState<number | ''>('');
  const [areas, setAreas] = useState<Area[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    if (!open) {
      return;
    }

    void fetchAreas()
      .then(setAreas)
      .catch(() => setAreas([]));
  }, [open]);

  if (!open) {
    return null;
  }

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setSubmitting(true);
    setError(null);

    try {
      const store = await createStore({
        name: name.trim(),
        area_id: areaId === '' ? null : Number(areaId),
      });
      onCreated(store.id);
      onClose();
      setName('');
      setAreaId('');
    } catch (submitError: unknown) {
      setError(submitError instanceof Error ? submitError.message : 'Create failed');
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="create-store-title"
        className="w-full max-w-lg rounded-xl border border-border bg-surface p-6 shadow-xl"
      >
        <h2 id="create-store-title" className="text-lg font-semibold text-foreground">
          Add new store
        </h2>
        <p className="mt-1 text-sm text-muted">Create a franchise unit record.</p>

        <form onSubmit={(event) => void handleSubmit(event)} className="mt-5 space-y-4">
          <FormField
            id="store-new-name"
            label="Name"
            value={name}
            onChange={(event) => setName(event.target.value)}
            required
          />

          <div>
            <label htmlFor="store-new-area" className="mb-1.5 block text-sm font-medium text-foreground">
              Area
            </label>
            <select
              id="store-new-area"
              value={areaId}
              onChange={(event) =>
                setAreaId(event.target.value === '' ? '' : Number(event.target.value))
              }
              className="block w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm"
            >
              <option value="">—</option>
              {areas.map((area) => (
                <option key={area.id} value={area.id}>
                  {area.name}
                </option>
              ))}
            </select>
          </div>

          {error ? <Alert variant="error">{error}</Alert> : null}

          <div className="flex justify-end gap-2 pt-2">
            <Button type="button" variant="secondary" onClick={onClose} disabled={submitting}>
              Cancel
            </Button>
            <Button type="submit" disabled={submitting || !name.trim()}>
              {submitting ? 'Creating…' : 'Create store'}
            </Button>
          </div>
        </form>
      </div>
    </div>
  );
}
