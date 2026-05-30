import { Link } from 'react-router-dom';
import { PageHeader } from '@/components/ui/PageHeader';
import { TextLink } from '@/components/ui/TextLink';

export function ForbiddenPage() {
  return (
    <div className="mx-auto max-w-lg px-4 py-16">
      <PageHeader
        title="Access denied"
        description="You are signed in, but your account does not have permission to view this page or perform this action."
      />
      <TextLink to="/" className="mt-6 inline-block text-sm">
        ← Back to dashboard
      </TextLink>
      <p className="mt-4 text-sm text-muted">
        If you think this is a mistake, contact your franchisor administrator.
      </p>
      <Link
        to="/profile"
        className="mt-2 inline-block text-sm text-link hover:underline"
      >
        View my profile
      </Link>
    </div>
  );
}
