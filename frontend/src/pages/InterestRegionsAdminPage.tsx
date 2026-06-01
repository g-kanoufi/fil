import { Fragment, useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { PageHeader } from '@/components/ui/PageHeader';
import { Card } from '@/components/ui/Card';
import { Alert } from '@/components/ui/Alert';
import { LoadingState } from '@/components/ui/LoadingState';
import { Button } from '@/components/ui/Button';
import {
  createInterestRegion,
  deleteInterestRegion,
  fetchInterestRegions,
  updateInterestRegion,
  type InterestRegion,
} from '@/lib/api/interestRegions';
import { useAuth } from '@/providers/AuthProvider';

interface RegionDraft {
  name: string;
  code: string;
  sort_order: string;
  status: InterestRegion['status'];
  legacy_term_id: string;
}

function emptyDraft(): RegionDraft {
  return {
    name: '',
    code: '',
    sort_order: '0',
    status: 'active',
    legacy_term_id: '',
  };
}

function regionToDraft(region: InterestRegion): RegionDraft {
  return {
    name: region.name,
    code: region.code ?? '',
    sort_order: String(region.sort_order),
    status: region.status,
    legacy_term_id: region.legacy_term_id ? String(region.legacy_term_id) : '',
  };
}

export function InterestRegionsAdminPage() {
  const { user } = useAuth();
  const canManage = user?.permissions.includes('settings.manage') ?? false;
  const [regions, setRegions] = useState<InterestRegion[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [expandedCountryId, setExpandedCountryId] = useState<number | null>(null);
  const [editingId, setEditingId] = useState<number | 'new-country' | 'new-subdivision' | null>(null);
  const [editingParentId, setEditingParentId] = useState<number | null>(null);
  const [draft, setDraft] = useState<RegionDraft>(emptyDraft());
  const [saving, setSaving] = useState(false);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      setRegions(await fetchInterestRegions());
    } catch (loadError: unknown) {
      setError(loadError instanceof Error ? loadError.message : 'Failed to load interest regions');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (canManage) {
      void load();
    }
  }, [canManage, load]);

  const subdivisionCount = regions.reduce((total, country) => total + (country.children?.length ?? 0), 0);

  function startCreateCountry() {
    setEditingId('new-country');
    setEditingParentId(null);
    setDraft(emptyDraft());
    setSuccess(null);
  }

  function startCreateSubdivision(countryId: number) {
    setExpandedCountryId(countryId);
    setEditingId('new-subdivision');
    setEditingParentId(countryId);
    setDraft(emptyDraft());
    setSuccess(null);
  }

  function startEdit(region: InterestRegion) {
    setEditingId(region.id);
    setEditingParentId(region.parent_id);
    setDraft(regionToDraft(region));
    setSuccess(null);

    if (region.parent_id === null) {
      setExpandedCountryId(region.id);
    }
  }

  function cancelEdit() {
    setEditingId(null);
    setEditingParentId(null);
    setDraft(emptyDraft());
  }

  async function saveDraft() {
    if (!draft.name.trim()) {
      setError('Name is required.');

      return;
    }

    setSaving(true);
    setError(null);
    setSuccess(null);

    const payload = {
      name: draft.name.trim(),
      code: draft.code.trim() || null,
      sort_order: Number.parseInt(draft.sort_order, 10) || 0,
      status: draft.status,
      legacy_term_id: draft.legacy_term_id.trim() ? Number.parseInt(draft.legacy_term_id, 10) : null,
    };

    try {
      if (editingId === 'new-country') {
        await createInterestRegion(payload);
        setSuccess('Country added.');
      } else if (editingId === 'new-subdivision' && editingParentId !== null) {
        await createInterestRegion({ ...payload, parent_id: editingParentId });
        setSuccess('Subdivision added.');
      } else if (typeof editingId === 'number') {
        await updateInterestRegion(editingId, {
          ...payload,
          parent_id: editingParentId,
        });
        setSuccess('Region updated.');
      }

      cancelEdit();
      await load();
    } catch (saveError: unknown) {
      setError(saveError instanceof Error ? saveError.message : 'Failed to save region');
    } finally {
      setSaving(false);
    }
  }

  async function removeRegion(region: InterestRegion) {
    if (!window.confirm(`Delete "${region.name}"? This cannot be undone.`)) {
      return;
    }

    setError(null);
    setSuccess(null);

    try {
      await deleteInterestRegion(region.id);
      setSuccess(`Deleted ${region.name}.`);
      await load();
    } catch (deleteError: unknown) {
      setError(deleteError instanceof Error ? deleteError.message : 'Failed to delete region');
    }
  }

  function renderDraftForm(label: string) {
    return (
      <div className="mt-3 space-y-3 rounded-md border border-border bg-surface-muted p-4">
        <p className="text-sm font-medium text-foreground">{label}</p>
        <div className="grid gap-3 md:grid-cols-2">
          <label className="block text-sm">
            <span className="mb-1 block text-muted">Name</span>
            <input
              className="w-full rounded-md border border-border bg-surface px-3 py-2"
              value={draft.name}
              onChange={(event) => setDraft((current) => ({ ...current, name: event.target.value }))}
            />
          </label>
          <label className="block text-sm">
            <span className="mb-1 block text-muted">Code</span>
            <input
              className="w-full rounded-md border border-border bg-surface px-3 py-2"
              value={draft.code}
              onChange={(event) => setDraft((current) => ({ ...current, code: event.target.value }))}
              placeholder="US, CA, TX…"
            />
          </label>
          <label className="block text-sm">
            <span className="mb-1 block text-muted">Sort order</span>
            <input
              className="w-full rounded-md border border-border bg-surface px-3 py-2"
              type="number"
              min={0}
              value={draft.sort_order}
              onChange={(event) => setDraft((current) => ({ ...current, sort_order: event.target.value }))}
            />
          </label>
          <label className="block text-sm">
            <span className="mb-1 block text-muted">Status</span>
            <select
              className="w-full rounded-md border border-border bg-surface px-3 py-2"
              value={draft.status}
              onChange={(event) =>
                setDraft((current) => ({
                  ...current,
                  status: event.target.value as InterestRegion['status'],
                }))
              }
            >
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </label>
          <label className="block text-sm md:col-span-2">
            <span className="mb-1 block text-muted">Legacy term ID (import mapping)</span>
            <input
              className="w-full rounded-md border border-border bg-surface px-3 py-2"
              type="number"
              min={1}
              value={draft.legacy_term_id}
              onChange={(event) =>
                setDraft((current) => ({ ...current, legacy_term_id: event.target.value }))
              }
              placeholder="Optional WordPress grabba_tax_area term ID"
            />
          </label>
        </div>
        <div className="flex gap-2">
          <Button type="button" onClick={() => void saveDraft()} disabled={saving}>
            {saving ? 'Saving…' : 'Save'}
          </Button>
          <Button type="button" variant="secondary" onClick={cancelEdit} disabled={saving}>
            Cancel
          </Button>
        </div>
      </div>
    );
  }

  if (!canManage) {
    return (
      <>
        <PageHeader title="Interest regions" description="Geography catalog for lead area-of-interest." />
        <Alert variant="warning">You do not have permission to manage interest regions.</Alert>
      </>
    );
  }

  return (
    <>
      <PageHeader
        title="Interest regions"
        description="US and Canada states/provinces used for lead “area of interest”. Separate from franchise territories."
        actions={
          <Link to="/settings" className="text-sm font-medium text-link hover:underline">
            ← Settings
          </Link>
        }
      />

      {error ? <Alert variant="danger">{error}</Alert> : null}
      {success ? <Alert variant="success">{success}</Alert> : null}

      <Card className="mb-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <p className="text-sm text-muted">
              {regions.length} countries · {subdivisionCount} states/provinces
            </p>
          </div>
          <Button type="button" onClick={startCreateCountry}>
            Add country
          </Button>
        </div>
        {editingId === 'new-country' ? renderDraftForm('New country') : null}
      </Card>

      {loading ? (
        <LoadingState label="Loading interest regions…" />
      ) : (
        <div className="space-y-4">
          {regions.map((country) => {
            const expanded = expandedCountryId === country.id;
            const children = country.children ?? [];

            return (
              <Card key={country.id}>
                <div className="flex flex-wrap items-start justify-between gap-3">
                  <div>
                    <button
                      type="button"
                      className="text-left text-base font-semibold text-foreground hover:text-link"
                      onClick={() => setExpandedCountryId(expanded ? null : country.id)}
                    >
                      {expanded ? '▾' : '▸'} {country.name}
                      {country.code ? ` (${country.code})` : ''}
                    </button>
                    <p className="text-sm text-muted">{children.length} subdivisions · status {country.status}</p>
                  </div>
                  <div className="flex flex-wrap gap-2">
                    <Button type="button" variant="secondary" onClick={() => startCreateSubdivision(country.id)}>
                      Add subdivision
                    </Button>
                    <Button type="button" variant="secondary" onClick={() => startEdit(country)}>
                      Edit
                    </Button>
                    <Button type="button" variant="danger" onClick={() => void removeRegion(country)}>
                      Delete
                    </Button>
                  </div>
                </div>

                {editingId === country.id ? renderDraftForm(`Edit ${country.name}`) : null}
                {editingId === 'new-subdivision' && editingParentId === country.id
                  ? renderDraftForm(`New subdivision in ${country.name}`)
                  : null}

                {expanded ? (
                  <ul className="mt-4 divide-y divide-border border-t border-border">
                    {children.length === 0 ? (
                      <li className="py-3 text-sm text-muted">No subdivisions yet.</li>
                    ) : (
                      children.map((child) => (
                        <Fragment key={child.id}>
                          <li className="flex flex-wrap items-center justify-between gap-3 py-3">
                            <div>
                              <p className="font-medium text-foreground">
                                {child.name}
                                {child.code ? ` (${child.code})` : ''}
                              </p>
                              <p className="text-xs text-muted">
                                sort {child.sort_order}
                                {child.legacy_term_id ? ` · legacy term ${child.legacy_term_id}` : ''}
                                {child.status !== 'active' ? ` · ${child.status}` : ''}
                              </p>
                            </div>
                            <div className="flex gap-2">
                              <Button type="button" variant="secondary" onClick={() => startEdit(child)}>
                                Edit
                              </Button>
                              <Button type="button" variant="danger" onClick={() => void removeRegion(child)}>
                                Delete
                              </Button>
                            </div>
                          </li>
                          {editingId === child.id ? (
                            <li className="pb-3">{renderDraftForm(`Edit ${child.name}`)}</li>
                          ) : null}
                        </Fragment>
                      ))
                    )}
                  </ul>
                ) : null}
              </Card>
            );
          })}
        </div>
      )}
    </>
  );
}
