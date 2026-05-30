import { FormEvent, useEffect, useMemo, useState, type ReactNode } from 'react';
import { Button } from '@/components/ui/Button';
import { CardHeader } from '@/components/ui/Card';
import { useAuth } from '@/providers/AuthProvider';
import { fetchFieldSchema, type FieldDef, type FieldGroupDef } from '@/lib/api/fields';

const SCALAR_TYPES = new Set([
  'text',
  'textarea',
  'number',
  'range',
  'select',
  'multiselect',
  'true_false',
  'date',
  'date_time',
  'email',
  'url',
]);

interface EntityCustomFieldsPanelProps {
  entity: 'lead' | 'store' | 'contact';
  values: Record<string, unknown>;
  canEdit: boolean;
  onSave: (custom: Record<string, unknown>) => Promise<void>;
}

function choiceOptions(field: FieldDef): Array<{ value: string; label: string }> {
  const choices = field.config?.choices;

  if (!choices) {
    return [];
  }

  if (Array.isArray(choices)) {
    return choices.map((item) => ({
      value: String(item.value),
      label: String(item.label ?? item.value),
    }));
  }

  return Object.entries(choices).map(([value, label]) => ({
    value,
    label: String(label),
  }));
}

function renderFieldInput(
  field: FieldDef,
  value: unknown,
  onChange: (next: unknown) => void,
): ReactNode {
  const id = `custom-${field.key}`;

  if (field.type === 'textarea') {
    return (
      <textarea
        id={id}
        rows={3}
        className="mt-1 block w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm"
        value={typeof value === 'string' ? value : ''}
        onChange={(event) => onChange(event.target.value)}
      />
    );
  }

  if (field.type === 'select') {
    const options = choiceOptions(field);

    return (
      <select
        id={id}
        className="mt-1 block w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm"
        value={typeof value === 'string' ? value : ''}
        onChange={(event) => onChange(event.target.value || null)}
      >
        <option value="">—</option>
        {options.map((option) => (
          <option key={option.value} value={option.value}>
            {option.label}
          </option>
        ))}
      </select>
    );
  }

  if (field.type === 'true_false') {
    return (
      <select
        id={id}
        className="mt-1 block w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm"
        value={value === true ? '1' : value === false ? '0' : ''}
        onChange={(event) => {
          if (event.target.value === '') {
            onChange(null);
          } else {
            onChange(event.target.value === '1');
          }
        }}
      >
        <option value="">—</option>
        <option value="1">Yes</option>
        <option value="0">No</option>
      </select>
    );
  }

  const inputType =
    field.type === 'number' || field.type === 'range'
      ? 'number'
      : field.type === 'date'
        ? 'date'
        : field.type === 'date_time'
          ? 'datetime-local'
          : field.type === 'email'
            ? 'email'
            : field.type === 'url'
              ? 'url'
              : 'text';

  return (
    <input
      id={id}
      type={inputType}
      className="mt-1 block w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm"
      value={value === null || value === undefined ? '' : String(value)}
      onChange={(event) => onChange(event.target.value || null)}
    />
  );
}

export function EntityCustomFieldsPanel({
  entity,
  values,
  canEdit,
  onSave,
}: EntityCustomFieldsPanelProps) {
  const { isFieldHidden, isFieldReadonly } = useAuth();
  const [groups, setGroups] = useState<FieldGroupDef[]>([]);
  const [draft, setDraft] = useState<Record<string, unknown>>(values);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    setDraft(values);
  }, [values]);

  useEffect(() => {
    let cancelled = false;

    void fetchFieldSchema(entity)
      .then((loaded) => {
        if (!cancelled) {
          setGroups(loaded);
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
  }, [entity]);

  const editableFields = useMemo(
    () =>
      groups.flatMap((group) =>
        group.fields.filter(
          (field) =>
            field.status === 'active'
            && SCALAR_TYPES.has(field.type)
            && field.storage === 'field_value'
            && !isFieldHidden(field.key),
        ),
      ),
    [groups, isFieldHidden],
  );

  async function onSubmit(event: FormEvent) {
    event.preventDefault();

    if (!canEdit) {
      return;
    }

    setSaving(true);

    try {
      const payload: Record<string, unknown> = {};

      for (const field of editableFields) {
        if (field.key in draft) {
          payload[field.key] = draft[field.key];
        }
      }

      await onSave(payload);
    } finally {
      setSaving(false);
    }
  }

  if (loading) {
    return <p className="text-sm text-muted">Loading custom fields…</p>;
  }

  if (editableFields.length === 0) {
    return null;
  }

  return (
    <form onSubmit={(event) => void onSubmit(event)} className="mt-6 space-y-4 border-t border-border pt-4">
      <CardHeader title="Custom fields" description="Schema-driven values for this record." />

      <div className="space-y-4">
        {editableFields.map((field) => {
          const readOnly = !canEdit || isFieldReadonly(field.key);

          return (
          <div key={field.id}>
            <label htmlFor={`custom-${field.key}`} className="block text-sm font-medium text-foreground">
              {field.name}
              {field.required ? ' *' : ''}
            </label>
            {readOnly ? (
              <p className="mt-1 text-sm text-foreground">
                {draft[field.key] === null || draft[field.key] === undefined || draft[field.key] === ''
                  ? '—'
                  : String(draft[field.key])}
              </p>
            ) : (
              renderFieldInput(field, draft[field.key], (next) =>
                setDraft((current) => ({ ...current, [field.key]: next })),
              )
            )}
          </div>
          );
        })}
      </div>

      {canEdit ? (
        <Button type="submit" size="sm" disabled={saving}>
          {saving ? 'Saving…' : 'Save custom fields'}
        </Button>
      ) : null}
    </form>
  );
}
