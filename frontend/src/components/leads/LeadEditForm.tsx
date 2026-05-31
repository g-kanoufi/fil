import { FormEvent, useEffect, useMemo, useState } from 'react';
import { Button } from '@/components/ui/Button';
import { CardHeader } from '@/components/ui/Card';
import { FormField } from '@/components/ui/FormField';
import { choiceOptions, tierOneFieldMap } from '@/lib/fields/fieldChoices';
import { fetchFieldSchema, type FieldDef } from '@/lib/api/fields';
import { updateLead, type Lead } from '@/lib/api/leads';

const TIER_ONE_KEYS = ['lead_status', 'lead_stage', 'lead_temp', 'lead_fdd_status', 'lead_source'] as const;

interface LeadEditFormProps {
  lead: Lead;
  canEdit: boolean;
  onUpdated: (lead: Lead) => void;
  onError: (message: string) => void;
}

function SchemaSelect({
  field,
  value,
  onChange,
}: {
  field: FieldDef;
  value: string;
  onChange: (next: string) => void;
}) {
  const options = choiceOptions(field);

  return (
    <label className="flex flex-col gap-1 text-sm">
      <span className="font-medium text-foreground">{field.name}</span>
      <select
        id={`lead-${field.key}`}
        className="rounded-lg border border-border bg-surface px-3 py-2 text-foreground"
        value={value}
        onChange={(event) => onChange(event.target.value)}
      >
        <option value="">—</option>
        {options.map((option) => (
          <option key={option.value} value={option.value}>
            {option.label}
          </option>
        ))}
      </select>
    </label>
  );
}

export function LeadEditForm({ lead, canEdit, onUpdated, onError }: LeadEditFormProps) {
  const [title, setTitle] = useState(lead.title);
  const [leadStatus, setLeadStatus] = useState(lead.lead_status ?? '');
  const [leadStage, setLeadStage] = useState(lead.lead_stage ?? '');
  const [leadTemp, setLeadTemp] = useState(lead.lead_temp ?? '');
  const [leadFddStatus, setLeadFddStatus] = useState(lead.lead_fdd_status ?? '');
  const [leadSource, setLeadSource] = useState(lead.lead_source ?? '');
  const [schemaFields, setSchemaFields] = useState<Map<string, FieldDef>>(new Map());
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    let cancelled = false;

    void fetchFieldSchema('lead')
      .then((groups) => {
        if (!cancelled) {
          setSchemaFields(tierOneFieldMap(groups, [...TIER_ONE_KEYS]));
        }
      })
      .catch(() => {
        if (!cancelled) {
          setSchemaFields(new Map());
        }
      });

    return () => {
      cancelled = true;
    };
  }, []);

  const fieldGetters = useMemo(
    () => ({
      lead_status: leadStatus,
      lead_stage: leadStage,
      lead_temp: leadTemp,
      lead_fdd_status: leadFddStatus,
      lead_source: leadSource,
    }),
    [leadStatus, leadStage, leadTemp, leadFddStatus, leadSource],
  );

  const fieldSetters = useMemo(
    () => ({
      lead_status: setLeadStatus,
      lead_stage: setLeadStage,
      lead_temp: setLeadTemp,
      lead_fdd_status: setLeadFddStatus,
      lead_source: setLeadSource,
    }),
    [],
  );

  async function onSubmit(event: FormEvent) {
    event.preventDefault();

    if (!canEdit) {
      return;
    }

    setSaving(true);
    onError('');

    try {
      const response = await updateLead(lead.id, {
        title: title.trim(),
        lead_status: leadStatus.trim() || null,
        lead_stage: leadStage.trim() || null,
        lead_temp: leadTemp.trim() || null,
        lead_fdd_status: leadFddStatus.trim() || null,
        lead_source: leadSource.trim() || null,
      });
      onUpdated(response.data);
    } catch (saveError: unknown) {
      onError(saveError instanceof Error ? saveError.message : 'Failed to save lead');
    } finally {
      setSaving(false);
    }
  }

  if (!canEdit) {
    return null;
  }

  function renderTierOneField(key: (typeof TIER_ONE_KEYS)[number]) {
    const field = schemaFields.get(key);
    const value = fieldGetters[key];

    if (field?.type === 'select') {
      return (
        <SchemaSelect
          key={key}
          field={field}
          value={value}
          onChange={(next) => fieldSetters[key](next)}
        />
      );
    }

    const labels: Record<(typeof TIER_ONE_KEYS)[number], string> = {
      lead_status: 'Status',
      lead_stage: 'Stage',
      lead_temp: 'Temperature',
      lead_fdd_status: 'FDD status',
      lead_source: 'Source',
    };

    return (
      <FormField
        key={key}
        label={field?.name ?? labels[key]}
        id={`lead-${key}`}
        value={value}
        onChange={(event) => fieldSetters[key](event.target.value)}
      />
    );
  }

  return (
    <form onSubmit={(event) => void onSubmit(event)} className="mt-6 space-y-4 border-t border-border pt-4">
      <CardHeader title="Edit lead" description="Core application fields driven by your field schema." />
      <FormField label="Title" id="lead-title" value={title} onChange={(event) => setTitle(event.target.value)} />
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {TIER_ONE_KEYS.map((key) => renderTierOneField(key))}
      </div>
      <Button type="submit" size="sm" disabled={saving}>
        {saving ? 'Saving…' : 'Save changes'}
      </Button>
    </form>
  );
}
