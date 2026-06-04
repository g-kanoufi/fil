import { FormEvent, useEffect, useMemo, useState } from 'react';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { FormField } from '@/components/ui/FormField';
import {
  fetchPortalApplication,
  updatePortalApplication,
  type PortalApplicationSnapshot,
} from '@/lib/api/portal';

export function PortalApplicationPage() {
  const [snapshot, setSnapshot] = useState<PortalApplicationSnapshot | null>(null);
  const [values, setValues] = useState<Record<string, unknown>>({});
  const [stepIndex, setStepIndex] = useState(0);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [savedMessage, setSavedMessage] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;

    void fetchPortalApplication()
      .then((data) => {
        if (cancelled) {
          return;
        }

        setSnapshot(data);
        setValues(data.values);
      })
      .catch(() => {
        if (!cancelled) {
          setError('Unable to load your application.');
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
  }, []);

  const currentStep = useMemo(() => snapshot?.steps[stepIndex] ?? null, [snapshot, stepIndex]);
  const isLastStep = snapshot ? stepIndex >= snapshot.steps.length - 1 : false;

  async function saveStep(event: FormEvent) {
    event.preventDefault();

    if (!currentStep) {
      return;
    }

    setSaving(true);
    setError(null);
    setSavedMessage(null);

    const stepValues = Object.fromEntries(
      currentStep.fields.map((field) => [field.key, values[field.key] ?? '']),
    );

    try {
      await updatePortalApplication(stepValues);
      setSavedMessage('Progress saved.');

      if (isLastStep) {
        const refreshed = await fetchPortalApplication();
        setSnapshot(refreshed);
        setValues(refreshed.values);
      } else {
        setStepIndex((index) => index + 1);
      }
    } catch {
      setError('Unable to save this step. Try again.');
    } finally {
      setSaving(false);
    }
  }

  if (loading) {
    return <p className="text-content-secondary">Loading application…</p>;
  }

  if (error && !snapshot) {
    return <Alert variant="danger">{error}</Alert>;
  }

  if (!snapshot || !currentStep) {
    return <Alert variant="info">No application fields are configured yet.</Alert>;
  }

  return (
    <div className="space-y-6">
      <div>
        <p className="text-sm text-content-secondary">{snapshot.lead.pipeline_phase_label}</p>
        <h1 className="text-2xl font-semibold text-foreground">{snapshot.lead.title}</h1>
      </div>

      {error ? <Alert variant="danger">{error}</Alert> : null}
      {savedMessage ? <Alert variant="success">{savedMessage}</Alert> : null}

      <Card padding="md">
        <p className="mb-4 text-sm font-medium text-content-secondary">
          Step {stepIndex + 1} of {snapshot.steps.length}: {currentStep.label}
        </p>

        <form onSubmit={saveStep} className="space-y-4">
          {currentStep.fields.map((field) => (
            <FormField
              key={field.key}
              label={field.name}
              id={`field-${field.key}`}
              type={field.type === 'date' ? 'date' : field.type === 'email' ? 'email' : 'text'}
              required={field.required}
              value={String(values[field.key] ?? '')}
              onChange={(event) =>
                setValues((current) => ({ ...current, [field.key]: event.target.value }))
              }
            />
          ))}

          <div className="flex items-center justify-between gap-3 pt-2">
            <Button
              type="button"
              variant="ghost"
              disabled={stepIndex === 0 || saving}
              onClick={() => setStepIndex((index) => Math.max(0, index - 1))}
            >
              Back
            </Button>
            <Button type="submit" disabled={saving}>
              {saving ? 'Saving…' : isLastStep ? 'Submit application' : 'Save and continue'}
            </Button>
          </div>
        </form>
      </Card>
    </div>
  );
}
