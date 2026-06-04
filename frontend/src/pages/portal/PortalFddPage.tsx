import { useEffect, useState } from 'react';
import { useParams, useSearchParams } from 'react-router-dom';
import { FddSignForm } from '@/components/fdd/FddSignForm';
import { Alert } from '@/components/ui/Alert';
import { Card } from '@/components/ui/Card';
import { LoadingState } from '@/components/ui/LoadingState';
import {
  beginPortalFddSignSession,
  fetchPortalFddDeliveries,
  signPortalFddDelivery,
  type PortalFddDelivery,
} from '@/lib/api/portal';

export function PortalFddPage() {
  const { deliveryId } = useParams();
  const [searchParams] = useSearchParams();
  const vendorReference = searchParams.get('ref') ?? undefined;
  const parsedId = Number(deliveryId);

  const [delivery, setDelivery] = useState<PortalFddDelivery | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [completed, setCompleted] = useState(false);

  useEffect(() => {
    if (!Number.isFinite(parsedId)) {
      setError('Invalid FDD delivery.');
      setLoading(false);
      return;
    }

    void fetchPortalFddDeliveries()
      .then((rows) => {
        const match = rows.find((row) => row.id === parsedId) ?? null;
        setDelivery(match);
        if (!match) {
          setError('FDD delivery not found.');
        } else if (!match.can_sign) {
          setCompleted(true);
        }
      })
      .catch(() => setError('Unable to load FDD signing.'))
      .finally(() => setLoading(false));

    if (vendorReference) {
      void beginPortalFddSignSession(parsedId).catch(() => undefined);
    }
  }, [parsedId, vendorReference]);

  if (loading) {
    return <LoadingState label="Loading FDD signing…" />;
  }

  if (completed) {
    return (
      <Card padding="md">
        <Alert variant="success">Your FDD receipt has been signed. Thank you.</Alert>
      </Card>
    );
  }

  if (error || !delivery) {
    return (
      <Card padding="md">
        <Alert variant="danger">{error ?? 'FDD delivery unavailable.'}</Alert>
      </Card>
    );
  }

  return (
    <Card padding="md">
      <h1 className="text-2xl font-semibold text-foreground">Sign FDD receipt (Item 23)</h1>
      <p className="mt-2 text-sm text-content-secondary">
        Acknowledge receipt of <strong>{delivery.fdd.title}</strong>.
      </p>

      <div className="mt-6">
        <FddSignForm
          idPrefix="portal-fdd"
          agreeLabel="I acknowledge receipt of the Franchise Disclosure Document and agree to sign electronically."
          submitLabel="Sign FDD receipt"
          onSubmit={async (payload) => {
            await signPortalFddDelivery(delivery.id, {
              signed_name: payload.signed_name,
              agree: true,
              vendor_reference: vendorReference,
            });
            setCompleted(true);
          }}
        />
      </div>
    </Card>
  );
}
