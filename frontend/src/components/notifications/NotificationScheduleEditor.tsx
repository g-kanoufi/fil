import type { NotificationRuleSchema, NotificationScheduleV2 } from '@/lib/notifications/ruleSchema';

interface NotificationScheduleEditorProps {
  enabled: boolean;
  value: NotificationScheduleV2;
  schema: NotificationRuleSchema;
  onEnabledChange: (enabled: boolean) => void;
  onChange: (value: NotificationScheduleV2) => void;
}

export function NotificationScheduleEditor({
  enabled,
  value,
  schema,
  onEnabledChange,
  onChange,
}: NotificationScheduleEditorProps) {
  return (
    <div className="space-y-3 rounded-lg border border-border bg-surface text-foreground p-4">
      <div className="flex flex-wrap items-center justify-between gap-2">
        <h3 className="text-sm font-medium text-foreground">Schedule</h3>
        <label className="flex items-center gap-2 text-sm">
          <input
            type="checkbox"
            checked={enabled}
            onChange={(event) => onEnabledChange(event.target.checked)}
          />
          <span>Date-based schedule</span>
        </label>
      </div>

      {!enabled ? (
        <p className="text-sm text-muted">Fires immediately when the trigger event occurs.</p>
      ) : (
        <div className="grid gap-3 md:grid-cols-2">
          <label className="flex flex-col gap-1 text-sm">
            <span className="font-medium text-foreground">Date field</span>
            <select
              className="rounded-lg border border-border bg-surface px-3 py-2"
              value={value.field}
              onChange={(event) => onChange({ ...value, field: event.target.value })}
            >
              {schema.schedule_fields.map((field) => (
                <option key={field} value={field}>
                  {field}
                </option>
              ))}
            </select>
          </label>

          <label className="flex flex-col gap-1 text-sm">
            <span className="font-medium text-foreground">Timing</span>
            <select
              className="rounded-lg border border-border bg-surface px-3 py-2"
              value={value.direction}
              onChange={(event) =>
                onChange({
                  ...value,
                  direction: event.target.value as NotificationScheduleV2['direction'],
                })
              }
            >
              {schema.schedule_directions.map((direction) => (
                <option key={direction.value} value={direction.value}>
                  {direction.label}
                </option>
              ))}
            </select>
          </label>

          <label className="flex flex-col gap-1 text-sm">
            <span className="font-medium text-foreground">Offset days</span>
            <input
              type="number"
              min={0}
              className="rounded-lg border border-border bg-surface px-3 py-2"
              value={value.offset_days}
              onChange={(event) => onChange({ ...value, offset_days: Number(event.target.value) || 0 })}
            />
          </label>

          <label className="flex flex-col gap-1 text-sm">
            <span className="font-medium text-foreground">Offset hours</span>
            <input
              type="number"
              min={0}
              className="rounded-lg border border-border bg-surface px-3 py-2"
              value={value.offset_hours}
              onChange={(event) => onChange({ ...value, offset_hours: Number(event.target.value) || 0 })}
            />
          </label>

          <label className="flex flex-col gap-1 text-sm">
            <span className="font-medium text-foreground">Window (days)</span>
            <input
              type="number"
              min={1}
              className="rounded-lg border border-border bg-surface px-3 py-2"
              value={value.window_days}
              onChange={(event) => onChange({ ...value, window_days: Math.max(1, Number(event.target.value) || 1) })}
            />
          </label>

          <label className="flex items-center gap-2 self-end text-sm">
            <input
              type="checkbox"
              checked={value.send_once}
              onChange={(event) => onChange({ ...value, send_once: event.target.checked })}
            />
            <span>Send once per lead</span>
          </label>
        </div>
      )}
    </div>
  );
}
