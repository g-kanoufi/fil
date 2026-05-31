import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { PageHeader } from '@/components/ui/PageHeader';
import { Card, CardHeader } from '@/components/ui/Card';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { LoadingState } from '@/components/ui/LoadingState';
import { fetchFieldGroups, type FieldDef } from '@/lib/api/fields';
import {
  createWidgetForm,
  fetchWidgetForm,
  fetchWidgetForms,
  rotateWidgetFormSiteKey,
  syncWidgetFormFields,
  type WidgetFormDef,
} from '@/lib/api/widgetForms';
import { useAuth } from '@/providers/AuthProvider';

interface CanvasField {
  field_id: number;
  field: FieldDef;
  label_override: string;
  placeholder: string;
  required_override: boolean;
  width: 'full' | 'half';
}

function toCanvasField(field: FieldDef, overrides?: Partial<CanvasField>): CanvasField {
  return {
    field_id: field.id,
    field,
    label_override: overrides?.label_override ?? '',
    placeholder: overrides?.placeholder ?? '',
    required_override: overrides?.required_override ?? field.required,
    width: overrides?.width ?? 'full',
  };
}

export function WidgetFormBuilderPage() {
  const { can } = useAuth();
  const canManage = can('fields.manage');

  const [forms, setForms] = useState<WidgetFormDef[]>([]);
  const [selectedId, setSelectedId] = useState<number | null>(null);
  const [leadFields, setLeadFields] = useState<FieldDef[]>([]);
  const [canvas, setCanvas] = useState<CanvasField[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [newForm, setNewForm] = useState({ key: '', name: '' });
  const [dragId, setDragId] = useState<number | null>(null);
  const [copyStatus, setCopyStatus] = useState<string | null>(null);
  const [rotating, setRotating] = useState(false);

  const selectedForm = useMemo(
    () => forms.find((form) => form.id === selectedId) ?? null,
    [forms, selectedId],
  );

  const embedSnippet = useMemo(() => {
    if (!selectedForm?.site_key) {
      return '';
    }

    const apiBase = window.location.origin;

    return `<div
  data-fil-widget="lead-form"
  data-site-key="${selectedForm.site_key}"
  data-api-base="${apiBase}"
></div>
<script src="${apiBase}/widget/form.js" defer></script>`;
  }, [selectedForm?.site_key]);

  const loadForm = useCallback(async (formId: number) => {
    const form = await fetchWidgetForm(formId);
    setCanvas(
      form.fields
        .filter((formField) => formField.field)
        .map((formField) =>
          toCanvasField(formField.field as FieldDef, {
            label_override: formField.label_override ?? '',
            placeholder: formField.placeholder ?? '',
            required_override: formField.required_override ?? undefined,
            width: (formField.width as 'full' | 'half') ?? 'full',
          }),
        ),
    );
  }, []);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const [loadedForms, groups] = await Promise.all([
        fetchWidgetForms(),
        fetchFieldGroups('lead', { context: 'widget' }),
      ]);
      setForms(loadedForms);
      setLeadFields(groups.flatMap((group) => group.fields));

      const firstId = loadedForms[0]?.id ?? null;
      setSelectedId(firstId);
      if (firstId) {
        await loadForm(firstId);
      } else {
        setCanvas([]);
      }
    } catch (loadError: unknown) {
      setError(loadError instanceof Error ? loadError.message : 'Failed to load widget forms');
    } finally {
      setLoading(false);
    }
  }, [loadForm]);

  useEffect(() => {
    if (canManage) {
      void load();
    }
  }, [canManage, load]);

  async function selectForm(formId: number) {
    setSelectedId(formId);
    setError(null);
    try {
      await loadForm(formId);
    } catch (selectError: unknown) {
      setError(selectError instanceof Error ? selectError.message : 'Failed to load form');
    }
  }

  async function handleCreateForm() {
    if (!newForm.key.trim() || !newForm.name.trim()) {
      setError('Form key and name are required.');
      return;
    }
    setError(null);
    try {
      const created = await createWidgetForm({
        key: newForm.key.trim(),
        name: newForm.name.trim(),
      });
      setNewForm({ key: '', name: '' });
      setForms((current) => [...current, created]);
      setSelectedId(created.id);
      setCanvas([]);
    } catch (createError: unknown) {
      setError(createError instanceof Error ? createError.message : 'Failed to create form');
    }
  }

  const canvasFieldIds = useMemo(() => new Set(canvas.map((c) => c.field_id)), [canvas]);
  const palette = useMemo(
    () => leadFields.filter((field) => !canvasFieldIds.has(field.id)),
    [leadFields, canvasFieldIds],
  );

  function addToCanvas(field: FieldDef) {
    setCanvas((current) => [...current, toCanvasField(field)]);
  }

  function removeFromCanvas(fieldId: number) {
    setCanvas((current) => current.filter((c) => c.field_id !== fieldId));
  }

  function updateCanvasField(fieldId: number, patch: Partial<CanvasField>) {
    setCanvas((current) =>
      current.map((c) => (c.field_id === fieldId ? { ...c, ...patch } : c)),
    );
  }

  function handleDrop(targetId: number) {
    if (dragId === null || dragId === targetId) {
      setDragId(null);
      return;
    }
    setCanvas((current) => {
      const ordered = [...current];
      const fromIndex = ordered.findIndex((c) => c.field_id === dragId);
      const toIndex = ordered.findIndex((c) => c.field_id === targetId);
      if (fromIndex === -1 || toIndex === -1) return current;
      const [moved] = ordered.splice(fromIndex, 1);
      ordered.splice(toIndex, 0, moved);
      return ordered;
    });
    setDragId(null);
  }

  async function handleRotateSiteKey() {
    if (!selectedId) return;
    setRotating(true);
    setError(null);
    try {
      const updated = await rotateWidgetFormSiteKey(selectedId);
      setForms((current) => current.map((f) => (f.id === updated.id ? updated : f)));
      setCopyStatus('Site key rotated — update the client embed snippet.');
    } catch (rotateError: unknown) {
      setError(rotateError instanceof Error ? rotateError.message : 'Failed to rotate site key');
    } finally {
      setRotating(false);
    }
  }

  async function copyEmbedSnippet() {
    if (!embedSnippet) return;
    try {
      await navigator.clipboard.writeText(embedSnippet);
      setCopyStatus('Embed snippet copied.');
    } catch {
      setCopyStatus('Could not copy — select the snippet manually.');
    }
  }

  async function handleSave() {
    if (!selectedId) return;
    setSaving(true);
    setError(null);
    try {
      const updated = await syncWidgetFormFields(
        selectedId,
        canvas.map((c, index) => ({
          field_id: c.field_id,
          sort_order: index,
          label_override: c.label_override.trim() || null,
          placeholder: c.placeholder.trim() || null,
          required_override: c.required_override,
          width: c.width,
        })),
      );
      setForms((current) => current.map((f) => (f.id === updated.id ? updated : f)));
    } catch (saveError: unknown) {
      setError(saveError instanceof Error ? saveError.message : 'Failed to save form');
    } finally {
      setSaving(false);
    }
  }

  if (!canManage) {
    return (
      <>
        <PageHeader title="Widget form builder" description="Admin access required." />
        <Alert variant="error">You do not have permission to manage widget forms.</Alert>
      </>
    );
  }

  return (
    <>
      <PageHeader
        title="Widget form builder"
        description="Drag Application and User fields into the embeddable lead form and reorder them."
      />

      <div className="mb-4 flex flex-wrap items-center gap-3">
        <Link to="/settings" className="text-sm text-link hover:underline">
          ← Settings
        </Link>
        <Link to="/settings/fields" className="text-sm text-link hover:underline">
          Manage custom fields →
        </Link>
        {forms.length > 0 ? (
          <label className="ml-auto flex items-center gap-2 text-sm">
            <span className="font-medium text-foreground">Form</span>
            <select
              value={selectedId ?? ''}
              onChange={(event) => void selectForm(Number(event.target.value))}
              className="rounded-lg border border-border bg-surface px-3 py-2"
            >
              {forms.map((form) => (
                <option key={form.id} value={form.id}>
                  {form.name} {form.site_key ? `(${form.site_key})` : '(default)'}
                </option>
              ))}
            </select>
          </label>
        ) : null}
      </div>

      {error ? (
        <Alert variant="error" className="mb-4">
          {error}
        </Alert>
      ) : null}

      <Card className="mb-6">
        <CardHeader
          title="New widget form"
          description="A unique site key is generated automatically for each form."
        />
        <div className="grid gap-3 md:grid-cols-2">
          <input
            value={newForm.key}
            onChange={(event) => setNewForm({ ...newForm, key: event.target.value })}
            placeholder="form key (lead_short)"
            className="rounded-lg border border-border bg-surface text-foreground px-3 py-2 text-sm"
          />
          <input
            value={newForm.name}
            onChange={(event) => setNewForm({ ...newForm, name: event.target.value })}
            placeholder="Display name"
            className="rounded-lg border border-border bg-surface text-foreground px-3 py-2 text-sm"
          />
        </div>
        <div className="mt-3">
          <Button size="sm" variant="secondary" onClick={() => void handleCreateForm()}>
            Create form
          </Button>
        </div>
      </Card>

      {loading ? <LoadingState label="Loading…" /> : null}

      {!loading && selectedForm ? (
        <Card className="mb-6">
          <CardHeader
            title="Embed on client site"
            description="The site key is publishable (safe in HTML). Intake is protected by key allowlisting, origin allowlisting in production, rate limits, and reCAPTCHA."
            actions={
              <Link to="/settings/widget/demo" className="text-sm text-link hover:underline">
                Open staff preview →
              </Link>
            }
          />
          <div className="space-y-3 text-sm text-muted">
            <ol className="list-decimal space-y-1 pl-5">
              <li>Copy the embed snippet into the client marketing page (HTML block, Webflow, etc.).</li>
              <li>
                Set <code className="text-foreground">data-api-base</code> to this FIL instance URL if the page
                is not on the same host.
              </li>
              <li>
                In production, ensure the client marketing origin is listed in{' '}
                <code className="text-foreground">FIL_EMBED_ALLOWED_ORIGINS</code> on the server.
              </li>
              <li>Submit a test lead and confirm it appears under Leads.</li>
              <li>If the key is exposed, rotate it and update the client snippet.</li>
            </ol>
            <div className="flex flex-wrap items-center gap-2">
              <span className="font-medium text-foreground">Site key</span>
              <code className="rounded bg-surface-muted px-2 py-1 text-xs text-foreground">
                {selectedForm.site_key ?? '(none)'}
              </code>
              <Button size="sm" variant="secondary" onClick={() => void handleRotateSiteKey()} disabled={rotating}>
                {rotating ? 'Rotating…' : 'Rotate site key'}
              </Button>
              <Button size="sm" variant="secondary" onClick={() => void copyEmbedSnippet()} disabled={!embedSnippet}>
                Copy embed snippet
              </Button>
            </div>
            {copyStatus ? <p className="text-foreground">{copyStatus}</p> : null}
            {embedSnippet ? (
              <pre className="overflow-x-auto rounded-lg border border-border bg-surface-muted p-3 text-xs text-foreground">
                {embedSnippet}
              </pre>
            ) : null}
          </div>
        </Card>
      ) : null}

      {!loading && selectedId ? (
        <div className="grid gap-4 md:grid-cols-2">
          <Card>
            <CardHeader
              title="Available fields"
              description="Application and User field groups only."
            />
            <ul className="flex flex-col gap-2">
              {palette.map((field) => (
                <li key={field.id}>
                  <button
                    type="button"
                    onClick={() => addToCanvas(field)}
                    className="flex w-full items-center justify-between rounded-lg border border-border bg-surface text-foreground px-3 py-2 text-left text-sm hover:bg-surface-muted"
                  >
                    <span>
                      <span className="font-medium text-foreground">{field.name}</span>
                      <span className="ml-2 text-xs text-muted">
                        {field.key} · {field.type}
                      </span>
                    </span>
                    <span aria-hidden className="text-link">
                      +
                    </span>
                  </button>
                </li>
              ))}
              {palette.length === 0 ? (
                <li className="text-sm text-muted">All eligible fields are on the form.</li>
              ) : null}
            </ul>
          </Card>

          <Card>
            <CardHeader
              title="Form layout"
              description="Drag to reorder."
              actions={
                <Button size="sm" onClick={() => void handleSave()} disabled={saving}>
                  {saving ? 'Saving…' : 'Save form'}
                </Button>
              }
            />
            <ul className="flex flex-col gap-3">
              {canvas.map((item) => (
                <li
                  key={item.field_id}
                  draggable
                  onDragStart={() => setDragId(item.field_id)}
                  onDragOver={(event) => event.preventDefault()}
                  onDrop={() => handleDrop(item.field_id)}
                  className="cursor-grab rounded-lg border border-border bg-surface text-foreground p-3"
                >
                  <div className="flex items-center justify-between gap-2">
                    <div className="flex items-center gap-2">
                      <span aria-hidden className="text-muted">
                        ⠿
                      </span>
                      <span className="font-medium text-foreground">{item.field.name}</span>
                      <span className="text-xs text-muted">
                        {item.field.key} · {item.field.type}
                      </span>
                    </div>
                    <Button size="sm" variant="ghost" onClick={() => removeFromCanvas(item.field_id)}>
                      Remove
                    </Button>
                  </div>
                  <div className="mt-2 grid gap-2 sm:grid-cols-2">
                    <input
                      value={item.label_override}
                      onChange={(event) =>
                        updateCanvasField(item.field_id, { label_override: event.target.value })
                      }
                      placeholder={`Label (${item.field.name})`}
                      className="rounded-lg border border-border bg-surface text-foreground px-2 py-1.5 text-sm"
                    />
                    <input
                      value={item.placeholder}
                      onChange={(event) =>
                        updateCanvasField(item.field_id, { placeholder: event.target.value })
                      }
                      placeholder="Placeholder"
                      className="rounded-lg border border-border bg-surface text-foreground px-2 py-1.5 text-sm"
                    />
                    <label className="flex items-center gap-2 text-sm">
                      <input
                        type="checkbox"
                        checked={item.required_override}
                        onChange={(event) =>
                          updateCanvasField(item.field_id, { required_override: event.target.checked })
                        }
                      />
                      Required
                    </label>
                    <select
                      value={item.width}
                      onChange={(event) =>
                        updateCanvasField(item.field_id, {
                          width: event.target.value as 'full' | 'half',
                        })
                      }
                      className="rounded-lg border border-border bg-surface text-foreground px-2 py-1.5 text-sm"
                    >
                      <option value="full">Full width</option>
                      <option value="half">Half width</option>
                    </select>
                  </div>
                </li>
              ))}
              {canvas.length === 0 ? (
                <li className="rounded-lg border border-dashed border-border px-3 py-6 text-center text-sm text-muted">
                  Add fields from the left to build the form.
                </li>
              ) : null}
            </ul>
          </Card>
        </div>
      ) : null}

      {!loading && !selectedId ? (
        <Alert variant="info">Create a widget form to start adding fields.</Alert>
      ) : null}
    </>
  );
}
