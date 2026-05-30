import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { TextLink } from '@/components/ui/TextLink';
import { DetailPanelChrome } from '@/components/ui/SlideOver';
import type { EntityDetailPageProps } from '@/components/detail/types';
import { EntityActivityTimeline } from '@/components/activity/EntityActivityTimeline';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { Card, CardHeader } from '@/components/ui/Card';
import { LoadingState } from '@/components/ui/LoadingState';
import { PageHeader } from '@/components/ui/PageHeader';
import { fetchContact, type Contact } from '@/lib/api/contacts';

function displayName(contact: Contact): string {
  const full = [contact.first_name, contact.last_name].filter(Boolean).join(' ').trim();

  return full || contact.name;
}

export function ContactDetailPage({
  recordId: recordIdProp,
  layout = 'page',
  onPanelClose,
  fullPagePath,
}: EntityDetailPageProps = {}) {
  const { id } = useParams<{ id: string }>();
  const contactId = recordIdProp ?? Number(id);
  const panelMode = layout === 'panel';
  const [contact, setContact] = useState<Contact | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!Number.isFinite(contactId)) {
      setError('Invalid contact id');
      setLoading(false);

      return;
    }

    let cancelled = false;

    void fetchContact(contactId)
      .then((data) => {
        if (!cancelled) {
          setContact(data);
        }
      })
      .catch((loadError: unknown) => {
        if (!cancelled) {
          setError(loadError instanceof Error ? loadError.message : 'Failed to load contact');
        }
      })
      .finally(() => {
        if (!cancelled) {
          setLoading(false);
        }
      });

    return () => {
      cancelled = true;
    };
  }, [contactId]);

  if (loading) {
    return <LoadingState label="Loading contact…" />;
  }

  if (!contact) {
    if (panelMode) {
      return <Alert variant="error">{error ?? 'Contact not found'}</Alert>;
    }

    return (
      <>
        <Alert variant="error">{error ?? 'Contact not found'}</Alert>
        <TextLink to="/reports/contacts" plain className="mt-4 inline-block text-sm">
          ← Back to contacts
        </TextLink>
      </>
    );
  }

  const detailBody = (
    <>
      {error ? <Alert variant="error" className="mb-4">{error}</Alert> : null}

      <Card>
        <CardHeader title="Contact details" />
        <dl className="grid grid-cols-2 gap-4 text-sm">
          <div>
            <dt className="text-muted">Email</dt>
            <dd className="mt-1 font-medium">{contact.email}</dd>
          </div>
          <div>
            <dt className="text-muted">Roles</dt>
            <dd className="mt-1 flex flex-wrap gap-2">
              {contact.roles.length > 0 ? (
                contact.roles.map((role) => <Badge key={role}>{role}</Badge>)
              ) : (
                <span className="font-medium">—</span>
              )}
            </dd>
          </div>
          <div>
            <dt className="text-muted">Updated</dt>
            <dd className="mt-1 font-medium">
              {contact.updated_at ? new Date(contact.updated_at).toLocaleString() : '—'}
            </dd>
          </div>
        </dl>
      </Card>

      <div className={panelMode ? 'mt-4' : 'mt-6'}>
        <EntityActivityTimeline subjectType="contact" subjectId={contact.id} />
      </div>
    </>
  );

  if (panelMode) {
    return (
      <>
        <DetailPanelChrome
          title={displayName(contact)}
          onClose={onPanelClose ?? (() => undefined)}
          openFullPageHref={fullPagePath ?? `/reports/contacts/${contact.id}`}
        />
        {detailBody}
      </>
    );
  }

  return (
    <>
      <PageHeader
        title={displayName(contact)}
        breadcrumbs={
          <TextLink to="/reports/contacts" plain>
            ← Contacts
          </TextLink>
        }
      />
      {detailBody}
    </>
  );
}
