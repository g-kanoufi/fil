import { apiGet, apiPatch, apiPut } from './client';
import type {
  NotificationConditionalsV2,
  NotificationRuleSchema,
  NotificationScheduleV2,
} from '@/lib/notifications/ruleSchema';

export type { NotificationConditionalsV2, NotificationScheduleV2, NotificationRuleSchema };

export interface NotificationProfileItem {
  id: number;
  title: string;
  trigger_slug: string;
}

export interface NotificationProfile {
  notifications: NotificationProfileItem[];
  disabled_notification_ids: number[];
}

export interface NotificationRule {
  id: number;
  hash: string | null;
  title: string;
  trigger_slug: string;
  normalized_trigger_slug: string;
  enabled: boolean;
  channel: string;
  subject: string | null;
  body_html: string | null;
  recipients: string[];
  conditionals: Record<string, unknown> | null;
  schedule: Record<string, unknown> | null;
  is_scheduled: boolean;
  profile_roles: string[] | null;
  deliveries_count?: number;
  updated_at: string;
}

export function fetchNotificationProfile(list = 'platform'): Promise<NotificationProfile> {
  return apiGet<{ data: NotificationProfile }>(`/v1/notifications/profile?list=${encodeURIComponent(list)}`)
    .then((body) => body.data);
}

export function saveNotificationPreferences(preferences: Record<number, boolean>): Promise<void> {
  const payload: Record<string, boolean> = {};

  for (const [id, optedIn] of Object.entries(preferences)) {
    payload[id] = optedIn;
  }

  return apiPut('/v1/notifications/profile', { preferences: payload }).then(() => undefined);
}

export function fetchNotificationRuleSchema(): Promise<NotificationRuleSchema> {
  return apiGet<{ data: NotificationRuleSchema }>('/v1/notifications/rules/schema').then((body) => body.data);
}

export function fetchNotificationRules(search = ''): Promise<NotificationRule[]> {
  const query = search ? `?search=${encodeURIComponent(search)}` : '';

  return apiGet<{ data: NotificationRule[] }>(`/v1/notifications/rules${query}`).then((body) => body.data);
}

export function updateNotificationRule(
  id: number,
  payload: Partial<
    Pick<
      NotificationRule,
      'enabled' | 'title' | 'subject' | 'body_html' | 'recipients' | 'trigger_slug'
    >
  > & {
    conditionals?: NotificationConditionalsV2 | null;
    schedule?: NotificationScheduleV2 | null;
  },
): Promise<NotificationRule> {
  return apiPatch<{ data: NotificationRule }>(`/v1/notifications/rules/${id}`, payload).then((body) => body.data);
}
