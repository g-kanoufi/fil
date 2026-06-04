import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { PageHeader } from '@/components/ui/PageHeader';
import { PageTabs } from '@/components/ui/PageTabs';
import { Card } from '@/components/ui/Card';
import { Alert } from '@/components/ui/Alert';
import { LoadingState } from '@/components/ui/LoadingState';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { InterestRegionsAdminPage } from '@/pages/InterestRegionsAdminPage';
import {
  createArea,
  deleteArea,
  fetchAreas,
  fetchNorthAmericaGeography,
  updateArea,
  type Area,
  type AreaTerritory,
  type NorthAmericaCountry,
} from '@/lib/api/areas';
import { useAuth } from '@/providers/AuthProvider';

interface AreaDraft {
  name: string;
  status: string;
  approval_status: string;
  country: string;
  subdivisions: string[];
}

function emptyDraft(): AreaDraft {
  return {
    name: '',
    status: 'active',
    approval_status: '',
    country: 'US',
    subdivisions: [],
  };
}

function draftFromArea(area: Area): AreaDraft {
  const territory = area.territory ?? {};

  return {
    name: area.name,
    status: area.status,
    approval_status: area.approval_status ?? '',
    country: territory.country ?? 'US',
    subdivisions: [...(territory.subdivisions ?? [])],
  };
}

function FranchiseAreasPanel({
  canManage,
  geography,
}: {
  canManage: boolean;
  geography: NorthAmericaCountry[];
}) {
  const [areas, setAreas] = useState<Area[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [editingId, setEditingId] = useState<number | 'new' | null>(null);
  const [draft, setDraft] = useState<AreaDraft>(emptyDraft());
  const [saving, setSaving] = useState(false);

  const country = useMemo(
    () => geography.find((entry) => entry.code === draft.country) ?? geography[0],
    [draft.country, geography],
  );

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      setAreas(await fetchAreas());
    } catch (loadError: unknown) {
      setError(loadError instanceof Error ? loadError.message : 'Failed to load areas');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void load();
  }, [load]);

  function startCreate() {
    setEditingId('new');
    setDraft(emptyDraft());
    setSuccess(null);
  }

  function startEdit(area: Area) {
    setEditingId(area.id);
    setDraft(draftFromArea(area));
    setSuccess(null);
  }

  function cancelEdit() {
    setEditingId(null);
    setDraft(emptyDraft());
  }

  function toggleSubdivision(code: string) {
    setDraft((current) => {
      const selected = new Set(current.subdivisions);

      if (selected.has(code)) {
        selected.delete(code);
      } else {
        selected.add(code);
      }

      return { ...current, subdivisions: Array.from(selected).sort() };
    });
  }

  async function save() {
    if (!canManage || editingId === null) {
      return;
    }

    setSaving(true);
    setError(null);
    setSuccess(null);

    const territory: AreaTerritory = {
      country: draft.country,
      subdivisions: draft.subdivisions,
    };

    try {
      if (editingId === 'new') {
        await createArea({
          name: draft.name.trim(),
          status: draft.status,
          approval_status: draft.approval_status.trim() || null,
          territory,
        });
        setSuccess('Area created.');
      } else {
        await updateArea(editingId, {
          name: draft.name.trim(),
          status: draft.status,
          approval_status: draft.approval_status.trim() || null,
          territory,
        });
        setSuccess('Area updated.');
      }

      cancelEdit();
      await load();
    } catch (saveError: unknown) {
      setError(saveError instanceof Error ? saveError.message : 'Failed to save area');
    } finally {
      setSaving(false);
    }
  }

  async function remove(area: Area) {
    if (!canManage || !window.confirm(`Delete area “${area.name}”?`)) {
      return;
    }

    setError(null);

    try {
      await deleteArea(area.id);
      setSuccess('Area deleted.');
      if (editingId === area.id) {
        cancelEdit();
      }
      await load();
    } catch (deleteError: unknown) {
      setError(deleteError instanceof Error ? deleteError.message : 'Failed to delete area');
    }
  }

  if (loading) {
    return <LoadingState label="Loading franchise areas…" />;
  }

  return (
    <div className="space-y-4">
      {error ? <Alert variant="error">{error}</Alert> : null}
      {success ? <Alert variant="success">{success}</Alert> : null}

      {canManage ? (
        <div className="flex flex-wrap gap-2">
          <Button type="button" onClick={startCreate}>
            Add area
          </Button>
        </div>
      ) : null}

      {editingId !== null ? (
        <Card className="space-y-4 p-4">
          <h2 className="text-lg font-semibold text-foreground">
            {editingId === 'new' ? 'New franchise area' : 'Edit franchise area'}
          </h2>
          <div className="grid gap-4 md:grid-cols-2">
            <label className="block text-sm">
              <span className="font-medium text-foreground">Name</span>
              <input
                className="mt-1 w-full rounded-md border border-border bg-background px-3 py-2"
                value={draft.name}
                onChange={(event) => setDraft((current) => ({ ...current, name: event.target.value }))}
                disabled={!canManage}
              />
            </label>
            <label className="block text-sm">
              <span className="font-medium text-foreground">Status</span>
              <select
                className="mt-1 w-full rounded-md border border-border bg-background px-3 py-2"
                value={draft.status}
                onChange={(event) => setDraft((current) => ({ ...current, status: event.target.value }))}
                disabled={!canManage}
              >
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </label>
          </div>

          <div>
            <p className="text-sm font-medium text-foreground">Territory (US &amp; Canada)</p>
            <div className="mt-2 flex flex-wrap gap-2">
              {geography.map((entry) => (
                <button
                  key={entry.code}
                  type="button"
                  className={`rounded-md border px-3 py-1.5 text-sm ${
                    draft.country === entry.code
                      ? 'border-primary bg-primary/10 font-medium text-foreground'
                      : 'border-border text-muted hover:text-foreground'
                  }`}
                  onClick={() => setDraft((current) => ({ ...current, country: entry.code, subdivisions: [] }))}
                  disabled={!canManage}
                >
                  {entry.name}
                </button>
              ))}
            </div>
            <div className="mt-3 max-h-64 overflow-y-auto rounded-md border border-border p-3">
              <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                {(country?.subdivisions ?? []).map((subdivision) => (
                  <label key={subdivision.code} className="flex items-center gap-2 text-sm">
                    <input
                      type="checkbox"
                      checked={draft.subdivisions.includes(subdivision.code)}
                      onChange={() => toggleSubdivision(subdivision.code)}
                      disabled={!canManage}
                    />
                    <span>
                      {subdivision.name} ({subdivision.code})
                    </span>
                  </label>
                ))}
              </div>
            </div>
          </div>

          {canManage ? (
            <div className="flex flex-wrap gap-2">
              <Button type="button" onClick={() => void save()} disabled={saving || draft.name.trim() === ''}>
                {saving ? 'Saving…' : 'Save'}
              </Button>
              <Button type="button" variant="secondary" onClick={cancelEdit}>
                Cancel
              </Button>
            </div>
          ) : null}
        </Card>
      ) : null}

      {areas.length === 0 ? (
        <EmptyState title="No franchise areas" description="Add a territory area to assign stores and royalties." />
      ) : (
        <Card className="overflow-x-auto">
          <table className="min-w-full text-left text-sm">
            <thead>
              <tr className="border-b border-border text-muted">
                <th className="px-4 py-3 font-medium">Name</th>
                <th className="px-4 py-3 font-medium">Status</th>
                <th className="px-4 py-3 font-medium">Territory</th>
                <th className="px-4 py-3 font-medium">Stores</th>
                {canManage ? <th className="px-4 py-3 font-medium">Actions</th> : null}
              </tr>
            </thead>
            <tbody>
              {areas.map((area) => {
                const subdivisionCount = area.territory?.subdivisions?.length ?? 0;
                const countryLabel =
                  geography.find((entry) => entry.code === area.territory?.country)?.name ??
                  area.territory?.country ??
                  '—';

                return (
                  <tr key={area.id} className="border-b border-border/70 last:border-0">
                    <td className="px-4 py-3 font-medium text-foreground">{area.name}</td>
                    <td className="px-4 py-3 capitalize text-muted">{area.status}</td>
                    <td className="px-4 py-3 text-muted">
                      {countryLabel}
                      {subdivisionCount > 0 ? ` · ${subdivisionCount} state(s)/province(s)` : ''}
                    </td>
                    <td className="px-4 py-3">
                      <Link
                        to={`/reports/stores?filter=store_area&subFilter=${area.id}`}
                        className="text-link hover:underline"
                      >
                        {area.store_count ?? 0}
                      </Link>
                    </td>
                    {canManage ? (
                      <td className="px-4 py-3">
                        <div className="flex flex-wrap gap-2">
                          <Button type="button" variant="secondary" size="sm" onClick={() => startEdit(area)}>
                            Edit
                          </Button>
                          <Button type="button" variant="secondary" size="sm" onClick={() => void remove(area)}>
                            Delete
                          </Button>
                        </div>
                      </td>
                    ) : null}
                  </tr>
                );
              })}
            </tbody>
          </table>
        </Card>
      )}
    </div>
  );
}

export function AreasAdminPage() {
  const { user } = useAuth();
  const canManage = user?.permissions.includes('stores.manage') ?? false;
  const canView = user?.permissions.includes('stores.view') ?? false;
  const [tab, setTab] = useState<'franchise' | 'catalog'>('franchise');
  const [geography, setGeography] = useState<NorthAmericaCountry[]>([]);
  const [geoError, setGeoError] = useState<string | null>(null);

  useEffect(() => {
    if (!canView) {
      return;
    }

    fetchNorthAmericaGeography()
      .then(setGeography)
      .catch((error: unknown) => {
        setGeoError(error instanceof Error ? error.message : 'Failed to load geography catalog');
      });
  }, [canView]);

  if (!canView) {
    return (
      <Alert variant="error">You do not have permission to view franchise areas.</Alert>
    );
  }

  return (
    <>
      <PageHeader
        title="Areas"
        description="Franchise territories and the US/Canada state & province catalog used for lead interest and area defaults."
      />

      <PageTabs
        ariaLabel="Areas views"
        value={tab}
        onChange={(id) => setTab(id as 'franchise' | 'catalog')}
        items={[
          { id: 'franchise', label: 'Franchise territories' },
          { id: 'catalog', label: 'US & Canada defaults' },
        ]}
      />

      {geoError ? <Alert variant="error">{geoError}</Alert> : null}

      <div role="tabpanel" id={`tabpanel-${tab}`} aria-labelledby={`tab-${tab}`}>
        {tab === 'franchise' ? (
          <FranchiseAreasPanel canManage={canManage} geography={geography} />
        ) : (
          <InterestRegionsAdminPage embedded />
        )}
      </div>
    </>
  );
}
