export interface NotificationCondition {
  field: string;
  op: string;
  value: string;
}

export interface NotificationConditionGroup {
  match: 'all' | 'any';
  conditions: NotificationCondition[];
}

export interface NotificationConditionalsV2 {
  v: 2;
  mode: 'always' | 'send_if' | 'skip_if';
  groups: NotificationConditionGroup[];
}

export interface NotificationScheduleV2 {
  v: 2;
  field: string;
  direction: 'on' | 'after' | 'before';
  offset_days: number;
  offset_hours: number;
  window_days: number;
  send_once: boolean;
}

export interface NotificationRuleSchema {
  triggers: Record<string, string>;
  condition_fields: string[];
  condition_operators: Array<{ value: string; label: string }>;
  schedule_fields: string[];
  schedule_directions: Array<{ value: string; label: string }>;
  recipient_tokens: string[];
}

export function emptyConditionals(): NotificationConditionalsV2 {
  return { v: 2, mode: 'always', groups: [] };
}

export function emptyCondition(): NotificationCondition {
  return { field: 'lead_status', op: 'eq', value: '' };
}

export function emptyConditionGroup(): NotificationConditionGroup {
  return { match: 'all', conditions: [emptyCondition()] };
}

export function emptySchedule(field = 'waiting_period_ends_at'): NotificationScheduleV2 {
  return {
    v: 2,
    field,
    direction: 'on',
    offset_days: 0,
    offset_hours: 0,
    window_days: 1,
    send_once: true,
  };
}

export function conditionalsFromRule(
  conditionals: Record<string, unknown> | null,
): NotificationConditionalsV2 {
  if (
    conditionals &&
    conditionals.v === 2 &&
    typeof conditionals.mode === 'string' &&
    Array.isArray(conditionals.groups)
  ) {
    return {
      v: 2,
      mode: conditionals.mode as NotificationConditionalsV2['mode'],
      groups: conditionals.groups.map((group) => {
        const row = group as Record<string, unknown>;

        return {
          match: row.match === 'any' ? 'any' : 'all',
          conditions: Array.isArray(row.conditions)
            ? row.conditions.map((condition) => {
                const item = condition as Record<string, unknown>;

                return {
                  field: String(item.field ?? 'lead_status'),
                  op: String(item.op ?? 'eq'),
                  value: String(item.value ?? ''),
                };
              })
            : [emptyCondition()],
        };
      }),
    };
  }

  return emptyConditionals();
}

export function scheduleFromRule(schedule: Record<string, unknown> | null): NotificationScheduleV2 | null {
  if (!schedule?.field) {
    return null;
  }

  return {
    v: 2,
    field: String(schedule.field),
    direction: (schedule.direction as NotificationScheduleV2['direction']) ?? 'on',
    offset_days: Number(schedule.offset_days ?? 0),
    offset_hours: Number(schedule.offset_hours ?? 0),
    window_days: Number(schedule.window_days ?? 1),
    send_once: schedule.send_once !== false,
  };
}
