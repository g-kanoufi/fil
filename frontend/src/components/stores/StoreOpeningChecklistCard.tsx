import { FormEvent, useEffect, useState } from 'react';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card, CardHeader } from '@/components/ui/Card';
import { FormField } from '@/components/ui/FormField';
import {
  fetchStoreOpeningChecklist,
  updateStoreOpeningChecklist,
  type StoreOpeningChecklist,
} from '@/lib/api/stores';

interface StoreOpeningChecklistCardProps {
  storeId: number;
  canEdit: boolean;
}

export function StoreOpeningChecklistCard({ storeId, canEdit }: StoreOpeningChecklistCardProps) {
  const [checklist, setChecklist] = useState<StoreOpeningChecklist | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;

    void fetchStoreOpeningChecklist(storeId)
      .then((data) => {
        if (!cancelled) {
          setChecklist(data);
        }
      })
      .catch(() => {
        if (!cancelled) {
          setError('Failed to load opening checklist.');
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
  }, [storeId]);

  async function onSubmit(event: FormEvent) {
    event.preventDefault();

    if (!checklist || !canEdit) {
      return;
    }

    setSaving(true);
    setError(null);

    try {
      setChecklist(
        await updateStoreOpeningChecklist(storeId, {
          items: checklist.items.map((item) => ({
            key: item.key,
            completed: Boolean(item.completed_at),
            notes: item.notes,
          })),
          buildout_started_at: checklist.timeline.buildout_started_at,
          expected_opening_at: checklist.timeline.expected_opening_at,
          opened_at: checklist.timeline.opened_at,
        }),
      );
    } catch {
      setError('Failed to save opening checklist.');
    } finally {
      setSaving(false);
    }
  }

  if (loading) {
    return <p className="text-sm text-muted">Loading opening checklist…</p>;
  }

  if (!checklist) {
    return error ? <Alert variant="error">{error}</Alert> : null;
  }

  return (
    <Card>
      <CardHeader
        title="Opening checklist"
        description="Build + Open milestones for this unit."
      />

      {error ? <Alert variant="error" className="mb-4">{error}</Alert> : null}

      <form onSubmit={onSubmit} className="space-y-4">
        <div className="grid gap-4 md:grid-cols-3">
          <FormField
            label="Build-out started"
            id="buildout_started_at"
            type="date"
            value={checklist.timeline.buildout_started_at ?? ''}
            disabled={!canEdit}
            onChange={(event) =>
              setChecklist((current) =>
                current
                  ? {
                      ...current,
                      timeline: { ...current.timeline, buildout_started_at: event.target.value || null },
                    }
                  : current,
              )
            }
          />
          <FormField
            label="Expected opening"
            id="expected_opening_at"
            type="date"
            value={checklist.timeline.expected_opening_at ?? ''}
            disabled={!canEdit}
            onChange={(event) =>
              setChecklist((current) =>
                current
                  ? {
                      ...current,
                      timeline: { ...current.timeline, expected_opening_at: event.target.value || null },
                    }
                  : current,
              )
            }
          />
          <FormField
            label="Opened"
            id="opened_at"
            type="date"
            value={checklist.timeline.opened_at ?? ''}
            disabled={!canEdit}
            onChange={(event) =>
              setChecklist((current) =>
                current
                  ? {
                      ...current,
                      timeline: { ...current.timeline, opened_at: event.target.value || null },
                    }
                  : current,
              )
            }
          />
        </div>

        <ul className="space-y-3">
          {checklist.items.map((item) => (
            <li key={item.key} className="rounded-lg border border-border px-3 py-3">
              <label className="flex items-start gap-3">
                <input
                  type="checkbox"
                  className="mt-1"
                  checked={Boolean(item.completed_at)}
                  disabled={!canEdit}
                  onChange={(event) =>
                    setChecklist((current) =>
                      current
                        ? {
                            ...current,
                            items: current.items.map((row) =>
                              row.key === item.key
                                ? {
                                    ...row,
                                    completed_at: event.target.checked ? new Date().toISOString() : null,
                                  }
                                : row,
                            ),
                          }
                        : current,
                    )
                  }
                />
                <span className="flex-1">
                  <span className="block font-medium text-foreground">{item.label}</span>
                  <FormField
                    label="Notes"
                    id={`notes-${item.key}`}
                    value={item.notes ?? ''}
                    disabled={!canEdit}
                    className="mt-2"
                    onChange={(event) =>
                      setChecklist((current) =>
                        current
                          ? {
                              ...current,
                              items: current.items.map((row) =>
                                row.key === item.key ? { ...row, notes: event.target.value } : row,
                              ),
                            }
                          : current,
                      )
                    }
                  />
                </span>
              </label>
            </li>
          ))}
        </ul>

        {canEdit ? (
          <Button type="submit" disabled={saving}>
            {saving ? 'Saving…' : 'Save checklist'}
          </Button>
        ) : null}
      </form>
    </Card>
  );
}
