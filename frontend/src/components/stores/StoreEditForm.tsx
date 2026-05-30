import { FormEvent, useEffect, useState } from 'react';
import { Button } from '@/components/ui/Button';
import { CardHeader } from '@/components/ui/Card';
import { FormField } from '@/components/ui/FormField';
import { fetchAreas, type Area } from '@/lib/api/areas';
import { updateStore, type Store } from '@/lib/api/stores';

interface StoreEditFormProps {
  store: Store;
  canEdit: boolean;
  onUpdated: (store: Store) => void;
  onError: (message: string) => void;
}

export function StoreEditForm({ store, canEdit, onUpdated, onError }: StoreEditFormProps) {
  const [name, setName] = useState(store.name);
  const [storeStatus, setStoreStatus] = useState(store.store_status ?? '');
  const [areaId, setAreaId] = useState(store.area_id ? String(store.area_id) : '');
  const [areas, setAreas] = useState<Area[]>([]);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    if (!canEdit) {
      return;
    }

    void fetchAreas().then(setAreas).catch(() => setAreas([]));
  }, [canEdit]);

  async function onSubmit(event: FormEvent) {
    event.preventDefault();

    if (!canEdit) {
      return;
    }

    setSaving(true);
    onError('');

    try {
      const updated = await updateStore(store.id, {
        name: name.trim(),
        store_status: storeStatus.trim() || null,
        area_id: areaId ? Number(areaId) : null,
      });
      onUpdated(updated);
    } catch (saveError: unknown) {
      onError(saveError instanceof Error ? saveError.message : 'Failed to save store');
    } finally {
      setSaving(false);
    }
  }

  if (!canEdit) {
    return null;
  }

  return (
    <form onSubmit={(event) => void onSubmit(event)} className="mt-4 space-y-4 border-t border-border pt-4">
      <CardHeader title="Edit store" />
      <FormField label="Name" id="store-name" value={name} onChange={(event) => setName(event.target.value)} />
      <div className="grid gap-4 sm:grid-cols-2">
        <FormField
          label="Status"
          id="store-status"
          value={storeStatus}
          onChange={(event) => setStoreStatus(event.target.value)}
        />
        <label className="flex flex-col gap-1 text-sm">
          <span className="font-medium text-foreground">Area</span>
          <select
            id="store-area"
            value={areaId}
            onChange={(event) => setAreaId(event.target.value)}
            className="rounded-lg border border-border bg-surface px-3 py-2"
          >
            <option value="">Not assigned</option>
            {areas.map((area) => (
              <option key={area.id} value={area.id}>
                {area.name}
              </option>
            ))}
          </select>
        </label>
      </div>
      <Button type="submit" size="sm" disabled={saving}>
        {saving ? 'Saving…' : 'Save changes'}
      </Button>
    </form>
  );
}
