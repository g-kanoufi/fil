import { FormEvent, useState } from 'react';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { FormField } from '@/components/ui/FormField';

export interface FddSignFormPayload {
  signed_name: string;
  agree: boolean;
}

interface FddSignFormProps {
  idPrefix?: string;
  agreeLabel?: string;
  submitLabel?: string;
  submittingLabel?: string;
  onSubmit: (payload: FddSignFormPayload) => Promise<void>;
  onCancel?: () => void;
  cancelLabel?: string;
}

export function FddSignForm({
  idPrefix = 'fdd-sign',
  agreeLabel = 'I confirm receipt and agree to the terms of this FDD delivery.',
  submitLabel = 'Sign FDD',
  submittingLabel = 'Signing…',
  onSubmit,
  onCancel,
  cancelLabel = 'Cancel',
}: FddSignFormProps) {
  const [signedName, setSignedName] = useState('');
  const [agree, setAgree] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setSubmitting(true);
    setError(null);

    try {
      await onSubmit({ signed_name: signedName.trim(), agree });
      setSignedName('');
      setAgree(false);
    } catch (submitError: unknown) {
      setError(submitError instanceof Error ? submitError.message : 'Signature failed');
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <form onSubmit={(event) => void handleSubmit(event)} className="space-y-4">
      <FormField
        id={`${idPrefix}-signed-name`}
        label="Full legal name"
        value={signedName}
        onChange={(event) => setSignedName(event.target.value)}
        required
      />

      <label className="flex items-start gap-2 text-sm text-foreground">
        <input
          type="checkbox"
          checked={agree}
          onChange={(event) => setAgree(event.target.checked)}
          className="mt-1 rounded border-border"
          required
        />
        <span>{agreeLabel}</span>
      </label>

      {error ? <Alert variant="error">{error}</Alert> : null}

      <div className={`flex gap-2 pt-2 ${onCancel ? 'justify-end' : ''}`}>
        {onCancel ? (
          <Button type="button" variant="secondary" onClick={onCancel} disabled={submitting}>
            {cancelLabel}
          </Button>
        ) : null}
        <Button type="submit" disabled={submitting || !signedName.trim() || !agree}>
          {submitting ? submittingLabel : submitLabel}
        </Button>
      </div>
    </form>
  );
}
