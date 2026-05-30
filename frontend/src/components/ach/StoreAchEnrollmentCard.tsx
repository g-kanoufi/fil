import { useCallback, useEffect, useState } from 'react';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card, CardHeader } from '@/components/ui/Card';
import { LoadingState } from '@/components/ui/LoadingState';
import { StoreDwollaEnrollment } from '@/components/ach/StoreDwollaEnrollment';
import { StorePlaidLinkButton } from '@/components/ach/StorePlaidLinkButton';
import {
  certifyAchOwnership,
  fetchStoreAchCustomer,
  type PlaidLinkCompleteResponse,
  type StoreAchEnrollment,
} from '@/lib/api/ach';

interface StoreAchEnrollmentCardProps {
  storeId: number;
}

export function StoreAchEnrollmentCard({ storeId }: StoreAchEnrollmentCardProps) {
  const [enrollment, setEnrollment] = useState<StoreAchEnrollment | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [status, setStatus] = useState<string | null>(null);
  const [certifying, setCertifying] = useState(false);

  const loadEnrollment = useCallback(async () => {
    setError(null);

    try {
      const data = await fetchStoreAchCustomer(storeId);
      setEnrollment(data);
    } catch (loadError: unknown) {
      setError(loadError instanceof Error ? loadError.message : 'Failed to load ACH enrollment');
    } finally {
      setLoading(false);
    }
  }, [storeId]);

  useEffect(() => {
    void loadEnrollment();
  }, [loadEnrollment]);

  function handlePlaidComplete(result: PlaidLinkCompleteResponse) {
    if (result.status === 'pending_verification') {
      setStatus('Bank account added — waiting for micro-deposit verification.');
    } else {
      setStatus('Bank account linked successfully.');
    }

    void loadEnrollment();
  }

  async function handleCertifyOwnership() {
    setCertifying(true);
    setError(null);

    try {
      await certifyAchOwnership(storeId);
      setStatus('Beneficial ownership certified.');
      await loadEnrollment();
    } catch (certifyError: unknown) {
      setError(certifyError instanceof Error ? certifyError.message : 'Certification failed');
    } finally {
      setCertifying(false);
    }
  }

  if (loading) {
    return <LoadingState label="Loading ACH enrollment…" />;
  }

  if (error) {
    return <Alert variant="error">{error}</Alert>;
  }

  if (!enrollment) {
    return null;
  }

  const kycComplete = enrollment.customer?.status === 'verified';
  const canLinkBank = enrollment.enrolled && (kycComplete || enrollment.dwolla_mode === 'sandbox');

  return (
    <Card>
      <CardHeader
        title="ACH enrollment"
        description="Complete Dwolla KYC, certify ownership, then link a bank account via Plaid."
      />

      {status ? <Alert variant="success" className="mb-4">{status}</Alert> : null}

      <div className="mb-4 flex flex-wrap items-center gap-2 text-sm">
        <span className="text-muted">Dwolla</span>
        <Badge>{enrollment.dwolla_mode === 'live' ? 'Live' : 'Sandbox'}</Badge>
        {enrollment.enrolled ? (
          <>
            <Badge variant={kycComplete ? 'success' : 'default'}>
              {enrollment.customer?.status ?? 'unknown'}
            </Badge>
            {enrollment.ownership_certified ? <Badge variant="success">Ownership certified</Badge> : null}
          </>
        ) : null}
      </div>

      {!enrollment.enrolled ? (
        <StoreDwollaEnrollment
          storeId={storeId}
          enrollment={enrollment}
          onEnrolled={() => {
            setStatus('ACH customer enrolled.');
            void loadEnrollment();
          }}
        />
      ) : (
        <>
          <div className="mb-4 text-sm">
            <span className="text-muted">Customer ID </span>
            <span className="font-medium">{enrollment.customer?.external_customer_id}</span>
          </div>

          {!enrollment.ownership_certified && enrollment.dwolla_mode === 'live' ? (
            <div className="mb-4">
              <Button size="sm" variant="secondary" disabled={certifying} onClick={() => void handleCertifyOwnership()}>
                {certifying ? 'Certifying…' : 'Certify beneficial ownership'}
              </Button>
            </div>
          ) : null}

          {canLinkBank ? (
            <>
              <div className="mb-4 flex flex-wrap items-center gap-2 text-sm">
                <span className="text-muted">Plaid</span>
                <Badge>{enrollment.plaid_mode === 'live' ? 'Live' : 'Sandbox'}</Badge>
              </div>

              {enrollment.funding_sources.length === 0 ? (
                <p className="mb-4 text-sm text-muted">No funding sources linked yet.</p>
              ) : (
                <ul className="mb-4 space-y-2 text-sm">
                  {enrollment.funding_sources.map((source) => (
                    <li
                      key={source.id}
                      className="flex items-center justify-between rounded-lg border border-border px-3 py-2"
                    >
                      <span>{source.name ?? source.external_funding_source_id}</span>
                      <span className="text-muted">
                        {source.is_default ? 'Default · ' : ''}
                        {source.status}
                      </span>
                    </li>
                  ))}
                </ul>
              )}

              {enrollment.plaid_pending_verification_accounts.length > 0 ? (
                <div className="mb-4">
                  <p className="mb-2 text-sm font-medium text-foreground">Pending verification</p>
                  <ul className="space-y-2 text-sm">
                    {enrollment.plaid_pending_verification_accounts.map((account) => (
                      <li
                        key={account.id}
                        className="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-amber-900"
                      >
                        {account.name ?? account.id}
                        {account.verification_status ? ` · ${account.verification_status}` : ''}
                      </li>
                    ))}
                  </ul>
                </div>
              ) : null}

              <StorePlaidLinkButton
                storeId={storeId}
                mode={enrollment.plaid_mode}
                onComplete={handlePlaidComplete}
              />
            </>
          ) : (
            <p className="text-sm text-muted">
              Complete Dwolla verification before linking a bank account. Current status:{' '}
              {enrollment.customer?.status ?? 'unknown'}.
            </p>
          )}
        </>
      )}
    </Card>
  );
}
