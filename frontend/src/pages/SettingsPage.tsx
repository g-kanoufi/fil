import { Link } from 'react-router-dom';
import { PageHeader } from '@/components/ui/PageHeader';
import { Card, CardHeader } from '@/components/ui/Card';
import { useAuth } from '@/providers/AuthProvider';

export function SettingsPage() {
  const { user } = useAuth();
  const canManage = user?.permissions.includes('settings.manage') ?? false;
  const canManageFields = user?.permissions.includes('fields.manage') ?? false;

  return (
    <>
      <PageHeader title="Settings" description="Platform configuration and personal preferences." />

      <div className="grid gap-4 md:grid-cols-2">
        <Card>
          <CardHeader title="Email preferences" description="Choose which platform emails you receive." />
          <Link to="/settings/notifications" className="text-sm font-medium text-link hover:underline">
            Manage my notifications →
          </Link>
        </Card>

        {canManage ? (
          <Card>
            <CardHeader
              title="Notification rules"
              description="Enable or inspect imported email rules, triggers, and schedules."
            />
            <Link
              to="/settings/notifications/rules"
              className="text-sm font-medium text-link hover:underline"
            >
              Manage notification rules →
            </Link>
          </Card>
        ) : null}

        {canManage ? (
          <Card>
            <CardHeader
              title="Mail delivery"
              description="Verify Mailgun domain and send a test email before production."
            />
            <Link to="/settings/mail" className="text-sm font-medium text-link hover:underline">
              Mail settings →
            </Link>
          </Card>
        ) : null}

        {canManage ? (
          <Card>
            <CardHeader
              title="Drip sequences"
              description="Configure automated email and SMS follow-up campaigns for leads."
            />
            <Link to="/settings/drips" className="text-sm font-medium text-link hover:underline">
              Manage drip sequences →
            </Link>
          </Card>
        ) : null}

        {canManageFields ? (
          <Card>
            <CardHeader
              title="Custom fields"
              description="Add application and entity fields of any type, including relational ones."
            />
            <Link to="/settings/fields" className="text-sm font-medium text-link hover:underline">
              Manage custom fields →
            </Link>
          </Card>
        ) : null}

        {canManageFields ? (
          <Card>
            <CardHeader
              title="Widget form builder"
              description="Drag application fields into the embeddable lead form and reorder them."
            />
            <div className="flex flex-col gap-2">
              <Link to="/settings/widget" className="text-sm font-medium text-link hover:underline">
                Build widget form →
              </Link>
              <Link to="/settings/widget/demo" className="text-sm font-medium text-link hover:underline">
                Preview embed widget →
              </Link>
            </div>
          </Card>
        ) : null}
      </div>
    </>
  );
}
