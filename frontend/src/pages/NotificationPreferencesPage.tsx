import { useCallback, useEffect, useState } from 'react';
import { Card, CardHeader } from '@/components/ui/Card';
import { PageHeader } from '@/components/ui/PageHeader';
import { Alert } from '@/components/ui/Alert';
import { LoadingState } from '@/components/ui/LoadingState';
import { Button } from '@/components/ui/Button';
import {
  fetchNotificationProfile,
  saveNotificationPreferences,
  type NotificationProfile,
} from '@/lib/api/notifications';
import { useAuth } from '@/providers/AuthProvider';

export function NotificationPreferencesPage() {
  const { user } = useAuth();
  const canManageRules = user?.permissions.includes('settings.manage') ?? false;
  const [profile, setProfile] = useState<NotificationProfile | null>(null);
  const [checked, setChecked] = useState<Record<number, boolean>>({});
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [status, setStatus] = useState<string | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      const data = await fetchNotificationProfile('platform');
      setProfile(data);
      const initial: Record<number, boolean> = {};

      for (const notification of data.notifications) {
        initial[notification.id] = !data.disabled_notification_ids.includes(notification.id);
      }

      setChecked(initial);
    } catch (loadError: unknown) {
      setError(loadError instanceof Error ? loadError.message : 'Failed to load preferences');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void load();
  }, [load]);

  async function onSave() {
    setSaving(true);
    setStatus(null);
    setError(null);

    try {
      await saveNotificationPreferences(checked);
      setStatus('Notification preferences saved.');
    } catch (saveError: unknown) {
      setError(saveError instanceof Error ? saveError.message : 'Failed to save preferences');
    } finally {
      setSaving(false);
    }
  }

  return (
    <>
      <PageHeader
        title="Email notifications"
        description="Choose which platform emails you receive. Transactional messages may still be sent when required."
      />

      {error ? <Alert variant="error" className="mb-4">{error}</Alert> : null}
      {status ? <Alert variant="success" className="mb-4">{status}</Alert> : null}

      {loading ? <LoadingState label="Loading preferences…" /> : null}

      {!loading && profile ? (
        <Card>
          <CardHeader title="Platform notifications" />
          {profile.notifications.length === 0 ? (
            <p className="text-sm text-muted">No configurable notifications for your role yet.</p>
          ) : (
            <ul className="space-y-3">
              {profile.notifications.map((notification) => (
                <li key={notification.id} className="flex items-start gap-3 rounded-lg border border-border px-4 py-3">
                  <input
                    id={`notification-${notification.id}`}
                    type="checkbox"
                    className="mt-1"
                    checked={checked[notification.id] ?? true}
                    onChange={(event) => {
                      setChecked((current) => ({
                        ...current,
                        [notification.id]: event.target.checked,
                      }));
                    }}
                  />
                  <label htmlFor={`notification-${notification.id}`} className="flex-1 cursor-pointer">
                    <span className="block text-sm font-medium text-foreground">{notification.title}</span>
                    <span className="mt-0.5 block text-xs text-muted">{notification.trigger_slug}</span>
                  </label>
                </li>
              ))}
            </ul>
          )}

          <div className="mt-6 border-t border-border pt-4 flex flex-wrap gap-3">
            <Button size="sm" onClick={() => void onSave()} disabled={saving || profile.notifications.length === 0}>
              {saving ? 'Saving…' : 'Save preferences'}
            </Button>
            {canManageRules ? (
              <a href="/settings/notifications/rules" className="self-center text-sm text-link hover:underline">
                Admin: notification rules
              </a>
            ) : null}
          </div>
        </Card>
      ) : null}
    </>
  );
}
