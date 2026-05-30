import { useState } from 'react';
import { Button } from '@/components/ui/Button';
import { cn } from '@/lib/cn';
import { formControl, formFocus } from '@/lib/ui/tokens';
import {
  centsToDollarInput,
  formatFeeCents,
  parseDollarsToCents,
  type ClosingFeeLine,
} from '@/lib/api/closings';

interface ClosingFeeLinesEditorProps {
  lines: ClosingFeeLine[];
  canEdit: boolean;
  saving?: boolean;
  onSave: (lines: ClosingFeeLine[]) => Promise<void>;
}

interface EditableLine {
  label: string;
  amount: string;
}

function toEditable(lines: ClosingFeeLine[]): EditableLine[] {
  if (lines.length === 0) {
    return [{ label: '', amount: '' }];
  }

  return lines.map((line) => ({
    label: line.label,
    amount: centsToDollarInput(line.amount_cents),
  }));
}

function fromEditable(lines: EditableLine[]): ClosingFeeLine[] {
  return lines
    .map((line) => ({
      label: line.label.trim(),
      amount_cents: parseDollarsToCents(line.amount),
    }))
    .filter((line) => line.label !== '');
}

export function ClosingFeeLinesEditor({
  lines,
  canEdit,
  saving = false,
  onSave,
}: ClosingFeeLinesEditorProps) {
  const [editing, setEditing] = useState(false);
  const [draft, setDraft] = useState<EditableLine[]>(() => toEditable(lines));
  const [error, setError] = useState<string | null>(null);

  const totalCents = lines.reduce((sum, line) => sum + line.amount_cents, 0);

  function startEdit() {
    setDraft(toEditable(lines));
    setEditing(true);
    setError(null);
  }

  function cancelEdit() {
    setDraft(toEditable(lines));
    setEditing(false);
    setError(null);
  }

  async function saveEdit() {
    setError(null);

    try {
      await onSave(fromEditable(draft));
      setEditing(false);
    } catch (saveError: unknown) {
      setError(saveError instanceof Error ? saveError.message : 'Failed to save fee lines');
    }
  }

  function updateLine(index: number, patch: Partial<EditableLine>) {
    setDraft((current) =>
      current.map((line, lineIndex) => (lineIndex === index ? { ...line, ...patch } : line)),
    );
  }

  function addLine() {
    setDraft((current) => [...current, { label: '', amount: '' }]);
  }

  function removeLine(index: number) {
    setDraft((current) => current.filter((_, lineIndex) => lineIndex !== index));
  }

  if (!editing) {
    return (
      <div>
        {lines.length === 0 ? (
          <p className="text-sm text-muted">No fee lines yet.</p>
        ) : (
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-border text-left text-muted">
                <th className="pb-2 font-medium">Description</th>
                <th className="pb-2 text-right font-medium">Amount</th>
              </tr>
            </thead>
            <tbody>
              {lines.map((line) => (
                <tr key={`${line.label}-${line.amount_cents}`} className="border-b border-border/60">
                  <td className="py-2">{line.label}</td>
                  <td className="py-2 text-right">{formatFeeCents(line.amount_cents)}</td>
                </tr>
              ))}
            </tbody>
            <tfoot>
              <tr>
                <td className="pt-3 font-medium">Total</td>
                <td className="pt-3 text-right font-medium">{formatFeeCents(totalCents)}</td>
              </tr>
            </tfoot>
          </table>
        )}

        {canEdit ? (
          <Button type="button" variant="secondary" className="mt-4" onClick={startEdit}>
            Edit fees
          </Button>
        ) : null}
      </div>
    );
  }

  return (
    <div className="space-y-3">
      <div className="hidden gap-3 text-xs font-medium uppercase tracking-wide text-muted md:grid md:grid-cols-[1fr_160px_auto]">
        <span>Description</span>
        <span>Amount (USD)</span>
        <span className="sr-only">Actions</span>
      </div>

      {draft.map((line, index) => (
        <div key={index} className="grid gap-3 md:grid-cols-[1fr_160px_auto]">
          <input
            aria-label={`Fee description ${index + 1}`}
            className={cn(formControl, formFocus)}
            value={line.label}
            onChange={(event) => updateLine(index, { label: event.target.value })}
            placeholder="Franchise fee"
          />
          <input
            aria-label={`Fee amount ${index + 1}`}
            className={cn(formControl, formFocus)}
            value={line.amount}
            onChange={(event) => updateLine(index, { amount: event.target.value })}
            inputMode="decimal"
            placeholder="0.00"
          />
          <div className="flex items-center">
            <Button type="button" variant="ghost" onClick={() => removeLine(index)}>
              Remove
            </Button>
          </div>
        </div>
      ))}

      <div className="flex flex-wrap gap-2">
        <Button type="button" variant="ghost" onClick={addLine}>
          Add line
        </Button>
        <Button type="button" onClick={() => void saveEdit()} disabled={saving}>
          {saving ? 'Saving…' : 'Save fees'}
        </Button>
        <Button type="button" variant="secondary" onClick={cancelEdit} disabled={saving}>
          Cancel
        </Button>
      </div>

      {error ? <p className="text-sm text-error">{error}</p> : null}
    </div>
  );
}
