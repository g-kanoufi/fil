import { FormEvent, useId, useState } from 'react';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { FormField } from '@/components/ui/FormField';
import { ModalDialog } from '@/components/ui/ModalDialog';
import { createLead } from '@/lib/api/leads';

interface CreateLeadModalProps {
  open: boolean;
  onClose: () => void;
  onCreated: (leadId: number) => void;
}

export function CreateLeadModal({ open, onClose, onCreated }: CreateLeadModalProps) {
  const titleId = useId();
  const descriptionId = useId();
  const [title, setTitle] = useState('');
  const [leadSource, setLeadSource] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setSubmitting(true);
    setError(null);

    try {
      const response = await createLead({
        title: title.trim(),
        lead_source: leadSource.trim() || undefined,
      });
      onCreated(response.data.id);
      onClose();
      setTitle('');
      setLeadSource('');
    } catch (submitError: unknown) {
      setError(submitError instanceof Error ? submitError.message : 'Create failed');
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <ModalDialog open={open} onClose={onClose} labelledBy={titleId} describedBy={descriptionId}>
      <h2 id={titleId} className="text-lg font-semibold text-foreground">
        Add new lead
      </h2>
      <p id={descriptionId} className="mt-1 text-sm text-muted">
        Create a lead record for staff follow-up.
      </p>

      <form onSubmit={(event) => void handleSubmit(event)} className="mt-5 space-y-4">
        <FormField
          id="lead-new-title"
          label="Title"
          value={title}
          onChange={(event) => setTitle(event.target.value)}
          required
        />
        <FormField
          id="lead-new-source"
          label="Source"
          value={leadSource}
          onChange={(event) => setLeadSource(event.target.value)}
          placeholder="e.g. referral, expo"
        />

        {error ? <Alert variant="error">{error}</Alert> : null}

        <div className="flex justify-end gap-2 pt-2">
          <Button type="button" variant="secondary" onClick={onClose} disabled={submitting}>
            Cancel
          </Button>
          <Button type="submit" disabled={submitting || !title.trim()}>
            {submitting ? 'Creating…' : 'Create lead'}
          </Button>
        </div>
      </form>
    </ModalDialog>
  );
}
