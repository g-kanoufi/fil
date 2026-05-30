import type { DripCampaignSchema, DripStep } from '@/lib/api/drips';

interface DripStepsEditorProps {
  steps: DripStep[];
  schema: DripCampaignSchema;
  onChange: (steps: DripStep[]) => void;
}

function emptyStep(sortOrder: number): DripStep {
  return {
    sort_order: sortOrder,
    delay_days: 0,
    delay_hours: 0,
    channel: 'email',
    subject: '',
    body_template: '',
    status: 'active',
  };
}

export function DripStepsEditor({ steps, schema, onChange }: DripStepsEditorProps) {
  function updateStep(index: number, step: DripStep) {
    onChange(steps.map((row, rowIndex) => (rowIndex === index ? step : row)));
  }

  function removeStep(index: number) {
    onChange(
      steps
        .filter((_, rowIndex) => rowIndex !== index)
        .map((step, rowIndex) => ({ ...step, sort_order: rowIndex + 1 })),
    );
  }

  function moveStep(index: number, direction: -1 | 1) {
    const target = index + direction;

    if (target < 0 || target >= steps.length) {
      return;
    }

    const next = [...steps];
    const [moved] = next.splice(index, 1);
    next.splice(target, 0, moved);
    onChange(next.map((step, rowIndex) => ({ ...step, sort_order: rowIndex + 1 })));
  }

  return (
    <div className="space-y-3 rounded-lg border border-border bg-surface text-foreground p-4">
      <div className="flex items-center justify-between gap-2">
        <h3 className="text-sm font-medium text-foreground">Sequence steps</h3>
        <button
          type="button"
          className="text-sm text-link hover:underline"
          onClick={() => onChange([...steps, emptyStep(steps.length + 1)])}
        >
          + Add step
        </button>
      </div>

      {steps.length === 0 ? (
        <p className="text-sm text-muted">No steps yet. Add at least one email or SMS step.</p>
      ) : null}

      {steps.map((step, index) => (
        <div key={step.id ?? `new-${index}`} className="space-y-3 rounded-lg border border-dashed border-border p-3">
          <div className="flex flex-wrap items-center justify-between gap-2">
            <span className="text-xs font-medium uppercase tracking-wide text-muted">
              Step {index + 1}
            </span>
            <div className="flex gap-2 text-xs">
              <button type="button" className="text-link hover:underline" onClick={() => moveStep(index, -1)}>
                Move up
              </button>
              <button type="button" className="text-link hover:underline" onClick={() => moveStep(index, 1)}>
                Move down
              </button>
              <button type="button" className="text-red-600 hover:underline" onClick={() => removeStep(index)}>
                Remove
              </button>
            </div>
          </div>

          <div className="grid gap-3 md:grid-cols-2">
            <label className="flex flex-col gap-1 text-sm">
              <span className="font-medium text-foreground">Channel</span>
              <select
                className="rounded-lg border border-border bg-surface px-3 py-2"
                value={step.channel}
                onChange={(event) =>
                  updateStep(index, { ...step, channel: event.target.value as DripStep['channel'] })
                }
              >
                {schema.channels.map((channel) => (
                  <option key={channel.value} value={channel.value}>
                    {channel.label}
                  </option>
                ))}
              </select>
            </label>

            <label className="flex flex-col gap-1 text-sm">
              <span className="font-medium text-foreground">Status</span>
              <select
                className="rounded-lg border border-border bg-surface px-3 py-2"
                value={step.status}
                onChange={(event) =>
                  updateStep(index, { ...step, status: event.target.value as DripStep['status'] })
                }
              >
                {schema.step_statuses.map((status) => (
                  <option key={status.value} value={status.value}>
                    {status.label}
                  </option>
                ))}
              </select>
            </label>

            <label className="flex flex-col gap-1 text-sm">
              <span className="font-medium text-foreground">Delay days</span>
              <input
                type="number"
                min={0}
                className="rounded-lg border border-border bg-surface px-3 py-2"
                value={step.delay_days}
                onChange={(event) =>
                  updateStep(index, { ...step, delay_days: Number(event.target.value) || 0 })
                }
              />
            </label>

            <label className="flex flex-col gap-1 text-sm">
              <span className="font-medium text-foreground">Delay hours</span>
              <input
                type="number"
                min={0}
                className="rounded-lg border border-border bg-surface px-3 py-2"
                value={step.delay_hours}
                onChange={(event) =>
                  updateStep(index, { ...step, delay_hours: Number(event.target.value) || 0 })
                }
              />
            </label>
          </div>

          {step.channel === 'email' ? (
            <label className="flex flex-col gap-1 text-sm">
              <span className="font-medium text-foreground">Subject</span>
              <input
                className="rounded-lg border border-border bg-surface px-3 py-2"
                value={step.subject ?? ''}
                onChange={(event) => updateStep(index, { ...step, subject: event.target.value })}
              />
            </label>
          ) : null}

          <label className="flex flex-col gap-1 text-sm">
            <span className="font-medium text-foreground">
              {step.channel === 'sms' ? 'SMS message' : 'Body template'}
            </span>
            <textarea
              className="min-h-24 rounded-lg border border-border bg-surface px-3 py-2 text-sm"
              value={step.body_template}
              onChange={(event) => updateStep(index, { ...step, body_template: event.target.value })}
            />
          </label>
        </div>
      ))}
    </div>
  );
}
