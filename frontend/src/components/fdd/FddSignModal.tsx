import { FormEvent, useState } from 'react';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { FormField } from '@/components/ui/FormField';

interface FddSignModalProps {
  deliveryLabel: string;
  open: boolean;
  onClose: () => void;
  onSubmit: (payload: { signed_name: string; agree: boolean }) => Promise<void>;
}

export function FddSignModal({ deliveryLabel, open, onClose, onSubmit }: FddSignModalProps) {
  const [signedName, setSignedName] = useState('');
  const [agree, setAgree] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  if (!open) {
    return null;
  }

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setSubmitting(true);
    setError(null);

    try {
      await onSubmit({ signed_name: signedName.trim(), agree });
      setSignedName('');
      setAgree(false);
      onClose();
    } catch (submitError: unknown) {
      setError(submitError instanceof Error ? submitError.message : 'Signature failed');
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="fdd-sign-title"
        className="w-full max-w-md rounded-xl border border-border bg-surface p-6 shadow-xl"
      >
        <h2 id="fdd-sign-title" className="text-lg font-semibold text-foreground">
          Record FDD signature
        </h2>
        <p className="mt-1 text-sm text-muted">{deliveryLabel}</p>

        <form onSubmit={(event) => void handleSubmit(event)} className="mt-5 space-y-4">
          <FormField
            id="signed_name"
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
            <span>I confirm receipt and agree to the terms of this FDD delivery.</span>
          </label>

          {error ? <Alert variant="error">{error}</Alert> : null}

          <div className="flex justify-end gap-2 pt-2">
            <Button type="button" variant="secondary" onClick={onClose} disabled={submitting}>
              Cancel
            </Button>
            <Button type="submit" disabled={submitting || !signedName.trim() || !agree}>
              {submitting ? 'Signing…' : 'Sign FDD'}
            </Button>
          </div>
        </form>
      </div>
    </div>
  );
}
