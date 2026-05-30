import { FormEvent, useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Card, CardHeader } from '@/components/ui/Card';
import { PageHeader } from '@/components/ui/PageHeader';
import { Alert } from '@/components/ui/Alert';
import { LoadingState } from '@/components/ui/LoadingState';
import { Button } from '@/components/ui/Button';
import { FormField } from '@/components/ui/FormField';
import { Badge } from '@/components/ui/Badge';
import { fetchProfile, updateProfile, type UserProfile } from '@/lib/api/profile';
import { useAuth } from '@/providers/AuthProvider';

function formatDate(value: string | null): string {
  if (!value) {
    return '—';
  }

  return new Date(value).toLocaleString();
}

export function ProfilePage() {
  const { user, refresh } = useAuth();
  const [profile, setProfile] = useState<UserProfile | null>(null);
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [currentPassword, setCurrentPassword] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  const applyProfile = useCallback((data: UserProfile) => {
    setProfile(data);
    setFirstName(data.first_name ?? '');
    setLastName(data.last_name ?? '');
    setEmail(data.email);
    setPhone(data.phone ?? '');
  }, []);

  useEffect(() => {
    void fetchProfile()
      .then(applyProfile)
      .catch((loadError: unknown) => {
        setError(loadError instanceof Error ? loadError.message : 'Failed to load profile');
      })
      .finally(() => {
        setLoading(false);
      });
  }, [applyProfile]);

  async function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSaving(true);
    setError(null);
    setSuccess(null);

    try {
      const payload = {
        first_name: firstName.trim(),
        last_name: lastName.trim(),
        email: email.trim(),
        phone: phone.trim(),
        ...(password
          ? {
              current_password: currentPassword,
              password,
              password_confirmation: passwordConfirmation,
            }
          : {}),
      };

      const updated = await updateProfile(payload);
      applyProfile(updated);
      setCurrentPassword('');
      setPassword('');
      setPasswordConfirmation('');
      setSuccess('Profile updated.');
      await refresh();
    } catch (saveError: unknown) {
      setError(saveError instanceof Error ? saveError.message : 'Failed to save profile');
    } finally {
      setSaving(false);
    }
  }

  if (loading) {
    return <LoadingState label="Loading profile…" />;
  }

  return (
    <>
      <PageHeader
        title="My profile"
        description="Manage your account details and password."
      />

      {error ? <Alert variant="error" className="mb-4">{error}</Alert> : null}
      {success ? <Alert variant="success" className="mb-4">{success}</Alert> : null}

      <div className="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        <Card>
          <CardHeader title="Account details" description="Update how you appear in FIL." />
          <form className="space-y-4" onSubmit={(event) => void onSubmit(event)}>
            <div className="grid gap-4 sm:grid-cols-2">
              <FormField
                label="First name"
                name="first_name"
                value={firstName}
                autoComplete="given-name"
                onChange={(event) => setFirstName(event.target.value)}
              />
              <FormField
                label="Last name"
                name="last_name"
                value={lastName}
                autoComplete="family-name"
                onChange={(event) => setLastName(event.target.value)}
              />
            </div>

            <FormField
              label="Email"
              name="email"
              type="email"
              value={email}
              required
              autoComplete="email"
              onChange={(event) => setEmail(event.target.value)}
            />

            <FormField
              label="Phone"
              name="phone"
              type="tel"
              value={phone}
              autoComplete="tel"
              onChange={(event) => setPhone(event.target.value)}
            />

            <div className="border-t border-border pt-4">
              <h3 className="text-sm font-semibold text-foreground">Change password</h3>
              <p className="mt-1 text-sm text-muted">Leave blank to keep your current password.</p>
              <div className="mt-4 space-y-4">
                <FormField
                  label="Current password"
                  name="current_password"
                  type="password"
                  value={currentPassword}
                  autoComplete="current-password"
                  onChange={(event) => setCurrentPassword(event.target.value)}
                />
                <div className="grid gap-4 sm:grid-cols-2">
                  <FormField
                    label="New password"
                    name="password"
                    type="password"
                    value={password}
                    autoComplete="new-password"
                    onChange={(event) => setPassword(event.target.value)}
                  />
                  <FormField
                    label="Confirm new password"
                    name="password_confirmation"
                    type="password"
                    value={passwordConfirmation}
                    autoComplete="new-password"
                    onChange={(event) => setPasswordConfirmation(event.target.value)}
                  />
                </div>
              </div>
            </div>

            <div className="flex flex-wrap gap-3 pt-2">
              <Button type="submit" disabled={saving}>
                {saving ? 'Saving…' : 'Save changes'}
              </Button>
              <Link
                to="/settings/notifications"
                className="inline-flex items-center rounded-lg border border-border px-4 py-2 text-sm font-medium text-foreground hover:bg-surface-muted"
              >
                Notification preferences
              </Link>
            </div>
          </form>
        </Card>

        <Card>
          <CardHeader title="Account summary" />
          <dl className="space-y-4 text-sm">
            <div>
              <dt className="font-medium text-foreground">Display name</dt>
              <dd className="mt-1 text-muted">{profile?.name ?? user?.name ?? '—'}</dd>
            </div>
            <div>
              <dt className="font-medium text-foreground">Primary role</dt>
              <dd className="mt-1">
                <Badge>{profile?.primary_role?.replace(/_/g, ' ') ?? '—'}</Badge>
              </dd>
            </div>
            <div>
              <dt className="font-medium text-foreground">Roles</dt>
              <dd className="mt-2 flex flex-wrap gap-2">
                {(profile?.roles ?? user?.roles ?? []).map((role) => (
                  <Badge key={role} variant="info">
                    {role.replace(/_/g, ' ')}
                  </Badge>
                ))}
              </dd>
            </div>
            <div>
              <dt className="font-medium text-foreground">Member since</dt>
              <dd className="mt-1 text-muted">{formatDate(profile?.created_at ?? null)}</dd>
            </div>
            <div>
              <dt className="font-medium text-foreground">Last updated</dt>
              <dd className="mt-1 text-muted">{formatDate(profile?.updated_at ?? null)}</dd>
            </div>
          </dl>
        </Card>
      </div>
    </>
  );
}
