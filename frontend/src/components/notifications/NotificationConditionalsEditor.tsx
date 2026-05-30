import type {
  NotificationCondition,
  NotificationConditionGroup,
  NotificationConditionalsV2,
  NotificationRuleSchema,
} from '@/lib/notifications/ruleSchema';
import { emptyCondition, emptyConditionGroup } from '@/lib/notifications/ruleSchema';

interface NotificationConditionalsEditorProps {
  value: NotificationConditionalsV2;
  schema: NotificationRuleSchema;
  onChange: (value: NotificationConditionalsV2) => void;
}

function updateGroup(
  groups: NotificationConditionGroup[],
  index: number,
  group: NotificationConditionGroup,
): NotificationConditionGroup[] {
  return groups.map((row, rowIndex) => (rowIndex === index ? group : row));
}

function updateCondition(
  conditions: NotificationCondition[],
  index: number,
  condition: NotificationCondition,
): NotificationCondition[] {
  return conditions.map((row, rowIndex) => (rowIndex === index ? condition : row));
}

export function NotificationConditionalsEditor({
  value,
  schema,
  onChange,
}: NotificationConditionalsEditorProps) {
  const showGroups = value.mode !== 'always';

  function setMode(mode: NotificationConditionalsV2['mode']) {
    onChange({
      ...value,
      mode,
      groups: mode === 'always' ? [] : value.groups.length > 0 ? value.groups : [emptyConditionGroup()],
    });
  }

  return (
    <div className="space-y-3 rounded-lg border border-border bg-surface text-foreground p-4">
      <div className="flex flex-wrap items-center justify-between gap-2">
        <h3 className="text-sm font-medium text-foreground">Conditions</h3>
        <label className="flex items-center gap-2 text-sm">
          <span className="text-muted">Mode</span>
          <select
            className="rounded-lg border border-border bg-surface px-2 py-1.5"
            value={value.mode}
            onChange={(event) => setMode(event.target.value as NotificationConditionalsV2['mode'])}
          >
            <option value="always">Always send</option>
            <option value="send_if">Send if matched</option>
            <option value="skip_if">Skip if matched</option>
          </select>
        </label>
      </div>

      {!showGroups ? (
        <p className="text-sm text-muted">No field conditions — this rule fires on every matching trigger.</p>
      ) : (
        <div className="space-y-4">
          {value.groups.map((group, groupIndex) => (
            <div key={groupIndex} className="space-y-2 rounded-lg border border-dashed border-border p-3">
              <div className="flex flex-wrap items-center gap-2">
                <span className="text-xs font-medium uppercase tracking-wide text-muted">Group {groupIndex + 1}</span>
                <select
                  className="rounded border border-border bg-surface px-2 py-1 text-sm"
                  value={group.match}
                  onChange={(event) =>
                    onChange({
                      ...value,
                      groups: updateGroup(value.groups, groupIndex, {
                        ...group,
                        match: event.target.value as 'all' | 'any',
                      }),
                    })
                  }
                >
                  <option value="all">Match all</option>
                  <option value="any">Match any</option>
                </select>
                {value.groups.length > 1 ? (
                  <button
                    type="button"
                    className="text-xs text-red-600 hover:underline"
                    onClick={() =>
                      onChange({
                        ...value,
                        groups: value.groups.filter((_, rowIndex) => rowIndex !== groupIndex),
                      })
                    }
                  >
                    Remove group
                  </button>
                ) : null}
              </div>

              {group.conditions.map((condition, conditionIndex) => (
                <div key={conditionIndex} className="grid gap-2 md:grid-cols-[1fr_1fr_1fr_auto]">
                  <select
                    className="rounded-lg border border-border bg-surface px-2 py-1.5 text-sm"
                    value={condition.field}
                    onChange={(event) =>
                      onChange({
                        ...value,
                        groups: updateGroup(value.groups, groupIndex, {
                          ...group,
                          conditions: updateCondition(group.conditions, conditionIndex, {
                            ...condition,
                            field: event.target.value,
                          }),
                        }),
                      })
                    }
                  >
                    {schema.condition_fields.map((field) => (
                      <option key={field} value={field}>
                        {field}
                      </option>
                    ))}
                  </select>
                  <select
                    className="rounded-lg border border-border bg-surface px-2 py-1.5 text-sm"
                    value={condition.op}
                    onChange={(event) =>
                      onChange({
                        ...value,
                        groups: updateGroup(value.groups, groupIndex, {
                          ...group,
                          conditions: updateCondition(group.conditions, conditionIndex, {
                            ...condition,
                            op: event.target.value,
                          }),
                        }),
                      })
                    }
                  >
                    {schema.condition_operators.map((operator) => (
                      <option key={operator.value} value={operator.value}>
                        {operator.label}
                      </option>
                    ))}
                  </select>
                  <input
                    className="rounded-lg border border-border bg-surface px-2 py-1.5 text-sm"
                    value={condition.value}
                    placeholder="Value"
                    disabled={condition.op === 'empty' || condition.op === 'not_empty' || condition.op === 'changed'}
                    onChange={(event) =>
                      onChange({
                        ...value,
                        groups: updateGroup(value.groups, groupIndex, {
                          ...group,
                          conditions: updateCondition(group.conditions, conditionIndex, {
                            ...condition,
                            value: event.target.value,
                          }),
                        }),
                      })
                    }
                  />
                  {group.conditions.length > 1 ? (
                    <button
                      type="button"
                      className="text-xs text-red-600 hover:underline"
                      onClick={() =>
                        onChange({
                          ...value,
                          groups: updateGroup(value.groups, groupIndex, {
                            ...group,
                            conditions: group.conditions.filter((_, rowIndex) => rowIndex !== conditionIndex),
                          }),
                        })
                      }
                    >
                      Remove
                    </button>
                  ) : (
                    <span />
                  )}
                </div>
              ))}

              <button
                type="button"
                className="text-sm text-link hover:underline"
                onClick={() =>
                  onChange({
                    ...value,
                    groups: updateGroup(value.groups, groupIndex, {
                      ...group,
                      conditions: [...group.conditions, emptyCondition()],
                    }),
                  })
                }
              >
                + Add condition
              </button>
            </div>
          ))}

          <button
            type="button"
            className="text-sm text-link hover:underline"
            onClick={() => onChange({ ...value, groups: [...value.groups, emptyConditionGroup()] })}
          >
            + Add condition group
          </button>
        </div>
      )}
    </div>
  );
}
