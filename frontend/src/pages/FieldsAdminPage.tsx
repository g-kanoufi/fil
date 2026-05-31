import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { PageHeader } from '@/components/ui/PageHeader';
import { Card, CardHeader } from '@/components/ui/Card';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { LoadingState } from '@/components/ui/LoadingState';
import {
  createField,
  deleteField,
  fetchFieldGroups,
  fetchRelatableEntities,
  reorderFields,
  type FieldDef,
  type FieldGroupDef,
  type FieldType,
  type RelatableCatalogue,
} from '@/lib/api/fields';
import { parseChoiceLines } from '@/lib/fields/fieldChoices';
import { useAuth } from '@/providers/AuthProvider';

const ENTITIES = ['lead', 'store', 'contact', 'area'] as const;

interface NewFieldDraft {
  field_group_id: number | null;
  key: string;
  name: string;
  type: FieldType;
  required: boolean;
  is_filterable: boolean;
  choicesText: string;
  related_entity: string;
}

function emptyDraft(): NewFieldDraft {
  return {
    field_group_id: null,
    key: '',
    name: '',
    type: 'text',
    required: false,
    is_filterable: false,
    choicesText: '',
    related_entity: '',
  };
}

function parseChoices(text: string): Array<{ value: string; label: string; aliases?: string[] }> {
  return parseChoiceLines(text);
}

function isChoiceType(type: FieldType): boolean {
  return type === 'select' || type === 'multiselect';
}

function isRelationType(type: FieldType): boolean {
  return type === 'relation_one' || type === 'relation_many';
}

export function FieldsAdminPage() {
  const { can } = useAuth();
  const canManage = can('fields.manage');

  const [entity, setEntity] = useState<(typeof ENTITIES)[number]>('lead');
  const [groups, setGroups] = useState<FieldGroupDef[]>([]);
  const [catalogue, setCatalogue] = useState<RelatableCatalogue | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [draft, setDraft] = useState<NewFieldDraft>(emptyDraft());
  const [saving, setSaving] = useState(false);
  const [dragKey, setDragKey] = useState<{ groupId: number; fieldId: number } | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const [loadedGroups, loadedCatalogue] = await Promise.all([
        fetchFieldGroups(entity),
        fetchRelatableEntities(),
      ]);
      setGroups(loadedGroups);
      setCatalogue(loadedCatalogue);
      setDraft((current) => ({
        ...current,
        field_group_id: current.field_group_id ?? loadedGroups[0]?.id ?? null,
      }));
    } catch (loadError: unknown) {
      setError(loadError instanceof Error ? loadError.message : 'Failed to load fields');
    } finally {
      setLoading(false);
    }
  }, [entity]);

  useEffect(() => {
    if (canManage) {
      void load();
    }
  }, [canManage, load]);

  async function handleCreate() {
    if (!draft.field_group_id || !draft.key.trim() || !draft.name.trim()) {
      setError('Group, key and name are required.');
      return;
    }

    setSaving(true);
    setError(null);

    const config: Record<string, unknown> = {};
    if (isChoiceType(draft.type)) {
      config.choices = parseChoices(draft.choicesText);
    }
    if (isRelationType(draft.type)) {
      config.related_entity = draft.related_entity;
    }

    try {
      await createField({
        field_group_id: draft.field_group_id,
        entity,
        key: draft.key.trim(),
        name: draft.name.trim(),
        type: draft.type,
        required: draft.required,
        is_filterable: draft.is_filterable,
        config: Object.keys(config).length > 0 ? config : null,
      });
      setDraft({ ...emptyDraft(), field_group_id: draft.field_group_id });
      await load();
    } catch (saveError: unknown) {
      setError(saveError instanceof Error ? saveError.message : 'Failed to create field');
    } finally {
      setSaving(false);
    }
  }

  async function handleDelete(field: FieldDef) {
    if (!window.confirm(`Delete field "${field.name}"? Stored values are removed.`)) {
      return;
    }
    setError(null);
    try {
      await deleteField(field.id);
      await load();
    } catch (deleteError: unknown) {
      setError(deleteError instanceof Error ? deleteError.message : 'Failed to delete field');
    }
  }

  async function handleDrop(group: FieldGroupDef, targetField: FieldDef) {
    if (!dragKey || dragKey.groupId !== group.id || dragKey.fieldId === targetField.id) {
      setDragKey(null);
      return;
    }

    const ordered = [...group.fields];
    const fromIndex = ordered.findIndex((f) => f.id === dragKey.fieldId);
    const toIndex = ordered.findIndex((f) => f.id === targetField.id);
    if (fromIndex === -1 || toIndex === -1) {
      setDragKey(null);
      return;
    }

    const [moved] = ordered.splice(fromIndex, 1);
    ordered.splice(toIndex, 0, moved);

    setGroups((current) =>
      current.map((g) => (g.id === group.id ? { ...g, fields: ordered } : g)),
    );
    setDragKey(null);

    try {
      await reorderFields(
        ordered.map((f, index) => ({ id: f.id, sort_order: index + 1, field_group_id: group.id })),
      );
    } catch (reorderError: unknown) {
      setError(reorderError instanceof Error ? reorderError.message : 'Failed to reorder');
      await load();
    }
  }

  if (!canManage) {
    return (
      <>
        <PageHeader title="Custom fields" description="Admin access required." />
        <Alert variant="error">You do not have permission to manage fields.</Alert>
      </>
    );
  }

  return (
    <>
      <PageHeader
        title="Custom fields"
        description="Define application and entity fields of any type, including relational ones."
      />

      <div className="mb-4 flex flex-wrap items-center gap-3">
        <Link to="/settings" className="text-sm text-link hover:underline">
          ← Settings
        </Link>
        <Link to="/settings/widget" className="text-sm text-link hover:underline">
          Widget form builder →
        </Link>
        <label className="ml-auto flex items-center gap-2 text-sm">
          <span className="font-medium text-foreground">Entity</span>
          <select
            value={entity}
            onChange={(event) => setEntity(event.target.value as (typeof ENTITIES)[number])}
            className="rounded-lg border border-border bg-surface px-3 py-2"
          >
            {ENTITIES.map((value) => (
              <option key={value} value={value}>
                {value}
              </option>
            ))}
          </select>
        </label>
      </div>

      {error ? (
        <Alert variant="error" className="mb-4">
          {error}
        </Alert>
      ) : null}

      <Card className="mb-6">
        <CardHeader title="Add field" description="New custom fields store values in field_values (no schema migration)." />
        <div className="grid gap-3 md:grid-cols-2">
          <label className="flex flex-col gap-1 text-sm">
            <span className="font-medium text-foreground">Group</span>
            <select
              value={draft.field_group_id ?? ''}
              onChange={(event) =>
                setDraft({ ...draft, field_group_id: Number(event.target.value) || null })
              }
              className="rounded-lg border border-border bg-surface text-foreground px-3 py-2"
            >
              {groups.map((group) => (
                <option key={group.id} value={group.id}>
                  {group.title}
                </option>
              ))}
            </select>
          </label>
          <label className="flex flex-col gap-1 text-sm">
            <span className="font-medium text-foreground">Type</span>
            <select
              value={draft.type}
              onChange={(event) => setDraft({ ...draft, type: event.target.value as FieldType })}
              className="rounded-lg border border-border bg-surface text-foreground px-3 py-2"
            >
              {(catalogue?.types ?? []).map((type) => (
                <option key={type} value={type}>
                  {type}
                </option>
              ))}
            </select>
          </label>
          <label className="flex flex-col gap-1 text-sm">
            <span className="font-medium text-foreground">Key (snake_case)</span>
            <input
              value={draft.key}
              onChange={(event) => setDraft({ ...draft, key: event.target.value })}
              placeholder="referral_notes"
              className="rounded-lg border border-border bg-surface text-foreground px-3 py-2"
            />
          </label>
          <label className="flex flex-col gap-1 text-sm">
            <span className="font-medium text-foreground">Label</span>
            <input
              value={draft.name}
              onChange={(event) => setDraft({ ...draft, name: event.target.value })}
              placeholder="Referral Notes"
              className="rounded-lg border border-border bg-surface text-foreground px-3 py-2"
            />
          </label>

          {isChoiceType(draft.type) ? (
            <label className="flex flex-col gap-1 text-sm md:col-span-2">
              <span className="font-medium text-foreground">
                Choices (one per line: value=Label or value=Label|legacy,alias)
              </span>
              <textarea
                value={draft.choicesText}
                onChange={(event) => setDraft({ ...draft, choicesText: event.target.value })}
                placeholder={'hot=Hot\nwarm=Warm\ncold=Cold'}
                className="min-h-24 rounded-lg border border-border bg-surface text-foreground px-3 py-2 font-mono text-xs"
              />
            </label>
          ) : null}

          {isRelationType(draft.type) ? (
            <label className="flex flex-col gap-1 text-sm">
              <span className="font-medium text-foreground">Related entity</span>
              <select
                value={draft.related_entity}
                onChange={(event) => setDraft({ ...draft, related_entity: event.target.value })}
                className="rounded-lg border border-border bg-surface text-foreground px-3 py-2"
              >
                <option value="">Select…</option>
                {(catalogue?.relatable_entities ?? []).map((option) => (
                  <option key={option.key} value={option.key}>
                    {option.label}
                  </option>
                ))}
              </select>
            </label>
          ) : null}

          <div className="flex items-center gap-4 text-sm">
            <label className="flex items-center gap-2">
              <input
                type="checkbox"
                checked={draft.required}
                onChange={(event) => setDraft({ ...draft, required: event.target.checked })}
              />
              Required
            </label>
            <label className="flex items-center gap-2">
              <input
                type="checkbox"
                checked={draft.is_filterable}
                onChange={(event) => setDraft({ ...draft, is_filterable: event.target.checked })}
              />
              Filterable
            </label>
          </div>
        </div>
        <div className="mt-4">
          <Button onClick={() => void handleCreate()} disabled={saving}>
            {saving ? 'Adding…' : 'Add field'}
          </Button>
        </div>
      </Card>

      {loading ? <LoadingState label="Loading fields…" /> : null}

      {!loading
        ? groups.map((group) => (
            <Card key={group.id} className="mb-4">
              <CardHeader title={group.title} description={`${group.fields.length} field(s) · drag to reorder`} />
              <ul className="divide-y divide-border">
                {group.fields.map((field) => (
                  <li
                    key={field.id}
                    draggable
                    onDragStart={() => setDragKey({ groupId: group.id, fieldId: field.id })}
                    onDragOver={(event) => event.preventDefault()}
                    onDrop={() => void handleDrop(group, field)}
                    className="flex cursor-grab items-center justify-between gap-3 py-2"
                  >
                    <div className="flex items-center gap-3">
                      <span aria-hidden className="text-muted">
                        ⠿
                      </span>
                      <div>
                        <div className="font-medium text-foreground">{field.name}</div>
                        <div className="text-xs text-muted">
                          {field.key} · {field.type}
                          {field.config?.related_entity ? ` → ${field.config.related_entity}` : ''}
                          {field.status !== 'active' ? ` · ${field.status}` : ''}
                        </div>
                      </div>
                    </div>
                    <Button size="sm" variant="danger" onClick={() => void handleDelete(field)}>
                      Delete
                    </Button>
                  </li>
                ))}
                {group.fields.length === 0 ? (
                  <li className="py-3 text-sm text-muted">No fields in this group yet.</li>
                ) : null}
              </ul>
            </Card>
          ))
        : null}
    </>
  );
}
