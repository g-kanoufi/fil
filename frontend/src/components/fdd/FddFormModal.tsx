import { FormEvent, useEffect, useState } from 'react';
import { fetchAreas, type Area } from '@/lib/api/areas';
import { createFdd, updateFdd, type Fdd, type FddFormPayload } from '@/lib/api/fdds';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { FormField } from '@/components/ui/FormField';
import { surface } from '@/lib/ui/tokens';

interface FddFormModalProps {
  open: boolean;
  fdd: Fdd | null;
  onClose: () => void;
  onSaved: (fdd: Fdd) => void;
}

export function FddFormModal({ open, fdd, onClose, onSaved }: FddFormModalProps) {
  const isEdit = fdd !== null;

  const [type, setType] = useState<'unit' | 'area'>('unit');
  const [title, setTitle] = useState('');
  const [areaId, setAreaId] = useState<number | ''>('');
  const [status, setStatus] = useState<'active' | 'inactive'>('active');
  const [pdf, setPdf] = useState<File | null>(null);
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

  useEffect(() => {
    if (!open) {
      return;
    }

    setType((fdd?.type as 'unit' | 'area') ?? 'unit');
    setTitle(fdd?.title ?? '');
    setAreaId(fdd?.area_id ?? '');
    setStatus((fdd?.status as 'active' | 'inactive') ?? 'active');
    setPdf(null);
    setError(null);
  }, [open, fdd]);

  if (!open) {
    return null;
  }

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setSubmitting(true);
    setError(null);

    const payload: FddFormPayload = {
      type,
      title: title.trim(),
      status,
      pdf,
    };

    if (type === 'area') {
      payload.area_id = areaId === '' ? null : Number(areaId);
    } else if (areaId !== '') {
      payload.area_id = Number(areaId);
    }

    try {
      const saved = isEdit && fdd
        ? await updateFdd(fdd.id, payload)
        : await createFdd(payload);

      onSaved(saved);
      onClose();
    } catch (submitError: unknown) {
      setError(submitError instanceof Error ? submitError.message : 'Save failed');
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="fdd-form-title"
        className="w-full max-w-lg rounded-xl border border-border bg-surface p-6 shadow-xl"
      >
        <h2 id="fdd-form-title" className="text-lg font-semibold text-foreground">
          {isEdit ? 'Edit FDD' : 'Upload new FDD'}
        </h2>
        <p className="mt-1 text-sm text-muted">
          {isEdit
            ? 'Update disclosure metadata or replace the PDF file.'
            : 'Add a unit or area franchise disclosure document to the catalog.'}
        </p>

        <form onSubmit={(event) => void handleSubmit(event)} className="mt-5 space-y-4">
          <div>
            <label htmlFor="fdd-type" className="mb-1.5 block text-sm font-medium text-foreground">
              FDD type
            </label>
            <select
              id="fdd-type"
              value={type}
              onChange={(event) => setType(event.target.value as 'unit' | 'area')}
              className="block w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm"
              required
            >
              <option value="unit">Unit FDD</option>
              <option value="area">Area FDD</option>
            </select>
          </div>

          <FormField
            id="fdd-title"
            label="Title"
            value={title}
            onChange={(event) => setTitle(event.target.value)}
            required
          />

          <div>
            <label htmlFor="fdd-area" className="mb-1.5 block text-sm font-medium text-foreground">
              Area {type === 'area' ? '(required)' : '(optional)'}
            </label>
            <select
              id="fdd-area"
              value={areaId}
              onChange={(event) =>
                setAreaId(event.target.value === '' ? '' : Number(event.target.value))
              }
              className="block w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm"
              required={type === 'area'}
            >
              <option value="">{type === 'unit' ? 'All areas' : 'Select an area'}</option>
              {areas.map((area) => (
                <option key={area.id} value={area.id}>
                  {area.name}
                </option>
              ))}
            </select>
          </div>

          <div>
            <label htmlFor="fdd-status" className="mb-1.5 block text-sm font-medium text-foreground">
              Status
            </label>
            <select
              id="fdd-status"
              value={status}
              onChange={(event) => setStatus(event.target.value as 'active' | 'inactive')}
              className="block w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm"
            >
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>

          <div>
            <label htmlFor="fdd-pdf" className="mb-1.5 block text-sm font-medium text-foreground">
              PDF file {isEdit ? '(optional — leave blank to keep current)' : '(required)'}
            </label>
            <input
              id="fdd-pdf"
              type="file"
              accept="application/pdf,.pdf"
              onChange={(event) => setPdf(event.target.files?.[0] ?? null)}
              className={`block w-full text-sm text-foreground ${surface.fileInputButton}`}
              required={!isEdit}
            />
            {isEdit && fdd?.document ? (
              <p className="mt-1 text-xs text-muted">Current file: {fdd.document.title}</p>
            ) : null}
          </div>

          {error ? <Alert variant="error">{error}</Alert> : null}

          <div className="flex justify-end gap-2 pt-2">
            <Button type="button" variant="secondary" onClick={onClose} disabled={submitting}>
              Cancel
            </Button>
            <Button type="submit" disabled={submitting || !title.trim() || (!isEdit && !pdf)}>
              {submitting ? 'Saving…' : isEdit ? 'Save changes' : 'Create FDD'}
            </Button>
          </div>
        </form>
      </div>
    </div>
  );
}
