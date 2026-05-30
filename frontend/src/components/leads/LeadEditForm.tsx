import { FormEvent, useState } from 'react';
import { Button } from '@/components/ui/Button';
import { CardHeader } from '@/components/ui/Card';
import { FormField } from '@/components/ui/FormField';
import { updateLead, type Lead } from '@/lib/api/leads';

interface LeadEditFormProps {
  lead: Lead;
  canEdit: boolean;
  onUpdated: (lead: Lead) => void;
  onError: (message: string) => void;
}

export function LeadEditForm({ lead, canEdit, onUpdated, onError }: LeadEditFormProps) {
  const [title, setTitle] = useState(lead.title);
  const [leadStatus, setLeadStatus] = useState(lead.lead_status ?? '');
  const [leadTemp, setLeadTemp] = useState(lead.lead_temp ?? '');
  const [leadSource, setLeadSource] = useState(lead.lead_source ?? '');
  const [saving, setSaving] = useState(false);

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
        lead_temp: leadTemp.trim() || null,
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

  return (
    <form onSubmit={(event) => void onSubmit(event)} className="mt-6 space-y-4 border-t border-border pt-4">
      <CardHeader title="Edit lead" description="Update core lead fields without changing pipeline stage." />
      <FormField label="Title" id="lead-title" value={title} onChange={(event) => setTitle(event.target.value)} />
      <div className="grid gap-4 sm:grid-cols-3">
        <FormField
          label="Status"
          id="lead-status"
          value={leadStatus}
          onChange={(event) => setLeadStatus(event.target.value)}
        />
        <FormField
          label="Temperature"
          id="lead-temp"
          value={leadTemp}
          onChange={(event) => setLeadTemp(event.target.value)}
        />
        <FormField
          label="Source"
          id="lead-source"
          value={leadSource}
          onChange={(event) => setLeadSource(event.target.value)}
        />
      </div>
      <Button type="submit" size="sm" disabled={saving}>
        {saving ? 'Saving…' : 'Save changes'}
      </Button>
    </form>
  );
}
