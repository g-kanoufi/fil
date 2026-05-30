import { Fragment, useCallback, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { sanitizeHtml } from '@/lib/security/sanitizeHtml';
import { PageHeader } from '@/components/ui/PageHeader';
import { Card } from '@/components/ui/Card';
import { Alert } from '@/components/ui/Alert';
import { LoadingState } from '@/components/ui/LoadingState';
import { Button } from '@/components/ui/Button';
import { NotificationConditionalsEditor } from '@/components/notifications/NotificationConditionalsEditor';
import { NotificationScheduleEditor } from '@/components/notifications/NotificationScheduleEditor';
import {
  fetchNotificationRuleSchema,
  fetchNotificationRules,
  updateNotificationRule,
  type NotificationRule,
  type NotificationRuleSchema,
} from '@/lib/api/notifications';
import {
  conditionalsFromRule,
  emptySchedule,
  scheduleFromRule,
  type NotificationConditionalsV2,
  type NotificationScheduleV2,
} from '@/lib/notifications/ruleSchema';
import { useAuth } from '@/providers/AuthProvider';

function triggerLabel(slug: string): string {
  return slug.replace(/\./g, ' · ').replace(/_/g, ' ');
}

function formatRecipient(entry: unknown): string {
  if (typeof entry === 'string' || typeof entry === 'number') {
    return String(entry);
  }

  if (entry && typeof entry === 'object') {
    const record = entry as Record<string, unknown>;
    const type = typeof record.type === 'string' ? record.type : '';
    const recipient = record.recipient ?? record.value ?? record.email ?? record.role;

    if (typeof recipient === 'string' || typeof recipient === 'number') {
      const value = String(recipient);

      if (value.startsWith('related:') || value.includes('@')) {
        return value;
      }

      if (type === 'role') {
        return `role:${value}`;
      }

      return type ? `related:${value}` : value;
    }
  }

  return '';
}

function formatRecipients(recipients: unknown[]): string {
  const formatted = recipients.map(formatRecipient).filter((value) => value.trim() !== '');

  return formatted.length > 0 ? formatted.join(', ') : '—';
}

interface RuleDraft {
  title: string;
  subject: string;
  body_html: string;
  recipients: string;
  trigger_slug: string;
  conditionals: NotificationConditionalsV2;
  scheduleEnabled: boolean;
  schedule: NotificationScheduleV2;
}

function scheduleSummary(schedule: NotificationRule['schedule']): string {
  if (!schedule?.field) {
    return '—';
  }

  const direction = schedule.direction ?? 'on';
  const offsetDays = schedule.offset_days ?? 0;
  const offsetHours = schedule.offset_hours ?? 0;

  return `${direction} ${String(schedule.field)}${offsetDays ? ` +${offsetDays}d` : ''}${offsetHours ? ` +${offsetHours}h` : ''}`;
}

function conditionSummary(conditionals: NotificationRule['conditionals']): string {
  if (!conditionals || conditionals.mode === 'always') {
    return 'Always';
  }

  const groupCount = Array.isArray(conditionals.groups) ? conditionals.groups.length : 0;

  return `${String(conditionals.mode)} · ${groupCount} group${groupCount === 1 ? '' : 's'}`;
}

export function NotificationRulesAdminPage() {
  const { user } = useAuth();
  const canManage = user?.permissions.includes('settings.manage') ?? false;
  const [rules, setRules] = useState<NotificationRule[]>([]);
  const [schema, setSchema] = useState<NotificationRuleSchema | null>(null);
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [expandedId, setExpandedId] = useState<number | null>(null);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [draft, setDraft] = useState<RuleDraft | null>(null);
  const [savingId, setSavingId] = useState<number | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      setRules(await fetchNotificationRules(search.trim()));
    } catch (loadError: unknown) {
      setError(loadError instanceof Error ? loadError.message : 'Failed to load notification rules');
    } finally {
      setLoading(false);
    }
  }, [search]);

  useEffect(() => {
    if (!canManage) {
      return;
    }

    void fetchNotificationRuleSchema()
      .then(setSchema)
      .catch((loadError: unknown) => {
        setError(loadError instanceof Error ? loadError.message : 'Failed to load rule schema');
      });
  }, [canManage]);

  useEffect(() => {
    if (canManage) {
      void load();
    }
  }, [canManage, load]);

  const enabledCount = useMemo(() => rules.filter((rule) => rule.enabled).length, [rules]);

  async function toggleEnabled(rule: NotificationRule) {
    setSavingId(rule.id);
    setError(null);

    try {
      const updated = await updateNotificationRule(rule.id, { enabled: !rule.enabled });
      setRules((current) => current.map((row) => (row.id === updated.id ? updated : row)));
    } catch (saveError: unknown) {
      setError(saveError instanceof Error ? saveError.message : 'Failed to update rule');
    } finally {
      setSavingId(null);
    }
  }

  function startEditing(rule: NotificationRule) {
    const schedule = scheduleFromRule(rule.schedule) ?? emptySchedule();

    setEditingId(rule.id);
    setExpandedId(rule.id);
    setDraft({
      title: rule.title,
      subject: rule.subject ?? '',
      body_html: rule.body_html ?? '',
      recipients: rule.recipients.join(', '),
      trigger_slug: rule.trigger_slug,
      conditionals: conditionalsFromRule(rule.conditionals),
      scheduleEnabled: Boolean(rule.schedule?.field) || rule.is_scheduled,
      schedule,
    });
  }

  function cancelEditing() {
    setEditingId(null);
    setDraft(null);
  }

  async function saveEditing(ruleId: number) {
    if (!draft) {
      return;
    }

    setSavingId(ruleId);
    setError(null);

    try {
      const updated = await updateNotificationRule(ruleId, {
        title: draft.title.trim(),
        subject: draft.subject.trim() || null,
        body_html: draft.body_html.trim() || null,
        recipients: draft.recipients
          .split(',')
          .map((token) => token.trim())
          .filter(Boolean),
        trigger_slug: draft.trigger_slug,
        conditionals: draft.conditionals,
        schedule: draft.scheduleEnabled ? draft.schedule : null,
      });

      setRules((current) => current.map((row) => (row.id === updated.id ? updated : row)));
      setEditingId(null);
      setDraft(null);
    } catch (saveError: unknown) {
      setError(saveError instanceof Error ? saveError.message : 'Failed to save rule');
    } finally {
      setSavingId(null);
    }
  }

  if (!canManage) {
    return (
      <>
        <PageHeader title="Notification rules" description="Admin access required." />
        <Alert variant="error">You do not have permission to manage notification rules.</Alert>
      </>
    );
  }

  return (
    <>
      <PageHeader
        title="Notification rules"
        description="Email rules with triggers, schedules, and conditions."
      />

      <div className="mb-4 flex flex-wrap items-center gap-3">
        <Link to="/settings/notifications" className="text-sm text-link hover:underline">
          ← My email preferences
        </Link>
      </div>

      {error ? <Alert variant="error" className="mb-4">{error}</Alert> : null}

      <Card className="mb-4">
        <div className="flex flex-wrap items-end gap-3">
          <label className="flex min-w-[16rem] flex-1 flex-col gap-1 text-sm">
            <span className="font-medium text-foreground">Search</span>
            <input
              type="search"
              value={search}
              onChange={(event) => setSearch(event.target.value)}
              placeholder="Title or trigger slug"
              className="rounded-lg border border-border bg-surface px-3 py-2"
            />
          </label>
          <Button size="sm" variant="secondary" onClick={() => void load()} disabled={loading}>
            Refresh
          </Button>
        </div>
        <p className="mt-3 text-sm text-muted">
          {enabledCount} enabled · {rules.length} total
        </p>
      </Card>

      {loading ? <LoadingState label="Loading rules…" /> : null}

      {!loading ? (
        <div className="overflow-x-auto rounded-xl border border-border bg-surface">
          <table className="w-full min-w-[960px] text-left text-sm">
            <thead>
              <tr className="border-b border-border text-muted">
                <th className="px-4 py-3 font-medium">On</th>
                <th className="px-4 py-3 font-medium">Rule</th>
                <th className="px-4 py-3 font-medium">Trigger</th>
                <th className="px-4 py-3 font-medium">Conditions</th>
                <th className="px-4 py-3 font-medium">Schedule</th>
                <th className="px-4 py-3 font-medium">Recipients</th>
                <th className="px-4 py-3 font-medium">Sent</th>
                <th className="px-4 py-3 font-medium" />
              </tr>
            </thead>
            <tbody>
              {rules.map((rule) => {
                const expanded = expandedId === rule.id;

                return (
                  <Fragment key={rule.id}>
                    <tr className="border-b border-border/70 align-top">
                      <td className="px-4 py-3">
                        <input
                          type="checkbox"
                          checked={rule.enabled}
                          disabled={savingId === rule.id}
                          onChange={() => void toggleEnabled(rule)}
                          aria-label={`Enable ${rule.title}`}
                        />
                      </td>
                      <td className="px-4 py-3">
                        <div className="font-medium text-foreground">{rule.title}</div>
                        {!rule.subject && !rule.body_html ? (
                          <div className="mt-1 text-xs text-amber-700">Missing email template</div>
                        ) : null}
                      </td>
                      <td className="px-4 py-3">
                        <div>{triggerLabel(rule.normalized_trigger_slug)}</div>
                        {rule.trigger_slug !== rule.normalized_trigger_slug ? (
                          <div className="mt-1 text-xs text-muted">{rule.trigger_slug}</div>
                        ) : null}
                      </td>
                      <td className="px-4 py-3">{conditionSummary(rule.conditionals)}</td>
                      <td className="px-4 py-3">{scheduleSummary(rule.schedule)}</td>
                      <td className="px-4 py-3 text-xs text-muted">{formatRecipients(rule.recipients)}</td>
                      <td className="px-4 py-3">{rule.deliveries_count ?? 0}</td>
                      <td className="px-4 py-3">
                        <div className="flex gap-2">
                          <Button
                            size="sm"
                            variant="secondary"
                            onClick={() => {
                              if (expanded) {
                                setExpandedId(null);
                                cancelEditing();
                              } else {
                                setExpandedId(rule.id);
                              }
                            }}
                          >
                            {expanded ? 'Hide' : 'Details'}
                          </Button>
                          {canManage ? (
                            <Button
                              size="sm"
                              variant="secondary"
                              onClick={() => (editingId === rule.id ? cancelEditing() : startEditing(rule))}
                            >
                              {editingId === rule.id ? 'Cancel edit' : 'Edit'}
                            </Button>
                          ) : null}
                        </div>
                      </td>
                    </tr>
                    {expanded ? (
                      <tr className="border-b border-border bg-surface-muted/80">
                        <td colSpan={8} className="px-4 py-4">
                          {editingId === rule.id && draft && schema ? (
                            <div className="grid gap-4">
                              <label className="flex flex-col gap-1 text-sm">
                                <span className="font-medium text-foreground">Title</span>
                                <input
                                  className="rounded-lg border border-border bg-surface text-foreground px-3 py-2"
                                  value={draft.title}
                                  onChange={(event) => setDraft({ ...draft, title: event.target.value })}
                                />
                              </label>
                              <label className="flex flex-col gap-1 text-sm">
                                <span className="font-medium text-foreground">Trigger</span>
                                <select
                                  className="rounded-lg border border-border bg-surface text-foreground px-3 py-2"
                                  value={draft.trigger_slug}
                                  onChange={(event) => {
                                    const triggerSlug = event.target.value;

                                    setDraft({
                                      ...draft,
                                      trigger_slug: triggerSlug,
                                      scheduleEnabled:
                                        triggerSlug === 'scheduled.leads' ? true : draft.scheduleEnabled,
                                    });
                                  }}
                                >
                                  {Object.entries(schema.triggers).map(([slug, label]) => (
                                    <option key={slug} value={slug}>
                                      {label}
                                    </option>
                                  ))}
                                </select>
                              </label>
                              <label className="flex flex-col gap-1 text-sm">
                                <span className="font-medium text-foreground">Subject</span>
                                <input
                                  className="rounded-lg border border-border bg-surface text-foreground px-3 py-2"
                                  value={draft.subject}
                                  onChange={(event) => setDraft({ ...draft, subject: event.target.value })}
                                />
                              </label>
                              <label className="flex flex-col gap-1 text-sm">
                                <span className="font-medium text-foreground">Recipients (comma-separated)</span>
                                <input
                                  className="rounded-lg border border-border bg-surface text-foreground px-3 py-2"
                                  list="notification-recipient-tokens"
                                  value={draft.recipients}
                                  onChange={(event) =>
                                    setDraft({ ...draft, recipients: event.target.value })
                                  }
                                  placeholder="related:prospect, related:lead_owner"
                                />
                                <datalist id="notification-recipient-tokens">
                                  {schema.recipient_tokens.map((token) => (
                                    <option key={token} value={token} />
                                  ))}
                                </datalist>
                              </label>

                              <NotificationConditionalsEditor
                                value={draft.conditionals}
                                schema={schema}
                                onChange={(conditionals) => setDraft({ ...draft, conditionals })}
                              />

                              <NotificationScheduleEditor
                                enabled={draft.scheduleEnabled}
                                value={draft.schedule}
                                schema={schema}
                                onEnabledChange={(scheduleEnabled) => setDraft({ ...draft, scheduleEnabled })}
                                onChange={(schedule) => setDraft({ ...draft, schedule })}
                              />

                              <label className="flex flex-col gap-1 text-sm">
                                <span className="font-medium text-foreground">Body HTML</span>
                                <textarea
                                  className="min-h-[10rem] rounded-lg border border-border bg-surface text-foreground px-3 py-2 font-mono text-xs"
                                  value={draft.body_html}
                                  onChange={(event) =>
                                    setDraft({ ...draft, body_html: event.target.value })
                                  }
                                />
                              </label>
                              <div>
                                <Button
                                  size="sm"
                                  onClick={() => void saveEditing(rule.id)}
                                  disabled={savingId === rule.id}
                                >
                                  Save changes
                                </Button>
                              </div>
                            </div>
                          ) : (
                            <dl className="grid gap-3 text-sm md:grid-cols-2">
                              <div>
                                <dt className="font-medium text-foreground">Subject</dt>
                                <dd className="mt-1 text-muted">{rule.subject ?? '—'}</dd>
                              </div>
                              <div>
                                <dt className="font-medium text-foreground">Profile roles</dt>
                                <dd className="mt-1 text-muted">
                                  {rule.profile_roles?.length ? rule.profile_roles.join(', ') : '—'}
                                </dd>
                              </div>
                              <div className="md:col-span-2">
                                <dt className="font-medium text-foreground">Conditions</dt>
                                <dd className="mt-1">
                                  <pre className="overflow-x-auto rounded-lg bg-surface text-foreground p-3 text-xs text-muted">
                                    {JSON.stringify(rule.conditionals ?? { mode: 'always' }, null, 2)}
                                  </pre>
                                </dd>
                              </div>
                              <div className="md:col-span-2">
                                <dt className="font-medium text-foreground">Schedule</dt>
                                <dd className="mt-1">
                                  <pre className="overflow-x-auto rounded-lg bg-surface text-foreground p-3 text-xs text-muted">
                                    {JSON.stringify(rule.schedule ?? null, null, 2)}
                                  </pre>
                                </dd>
                              </div>
                              {rule.body_html ? (
                                <div className="md:col-span-2">
                                  <dt className="font-medium text-foreground">Body preview</dt>
                                  <dd
                                    className="prose prose-sm mt-2 max-w-none rounded-lg border border-border bg-surface text-foreground p-3"
                                    dangerouslySetInnerHTML={{ __html: sanitizeHtml(rule.body_html) }}
                                  />
                                </div>
                              ) : null}
                            </dl>
                          )}
                        </td>
                      </tr>
                    ) : null}
                  </Fragment>
                );
              })}
            </tbody>
          </table>

          {rules.length === 0 ? (
            <p className="px-4 py-8 text-center text-sm text-muted">No notification rules found.</p>
          ) : null}
        </div>
      ) : null}
    </>
  );
}
