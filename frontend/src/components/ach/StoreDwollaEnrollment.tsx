import { useCallback, useEffect, useState } from 'react';
import { DwollaBusinessVCR, useDwollaWeb } from '@dwolla/react-drop-ins';
import { ExternalTextLink } from '@/components/ui/TextLink';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { enrollStoreAch, type StoreAchEnrollment } from '@/lib/api/ach';

interface DwollaSuccessResult {
  resource?: string;
  response?: {
    location?: string;
  };
}

interface StoreDwollaEnrollmentProps {
  storeId: number;
  enrollment: StoreAchEnrollment;
  onEnrolled: () => void;
}

export function StoreDwollaEnrollment({ storeId, enrollment, onEnrolled }: StoreDwollaEnrollmentProps) {
  const [open, setOpen] = useState(false);
  const [step, setStep] = useState<'intro' | 'kyc'>('intro');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const tokenUrl = `/api/v1/stores/${storeId}/ach/dwolla/client-token`;

  const handleSuccess = useCallback(
    async (result: DwollaSuccessResult) => {
      if (result.resource !== 'customers') {
        return;
      }

      const location = result.response?.location ?? '';
      const customerId = location.split('/').pop();

      if (!customerId) {
        setError('Dwolla enrollment succeeded but no customer id was returned.');
        return;
      }

      setLoading(true);
      setError(null);

      try {
        await enrollStoreAch(storeId, { external_customer_id: customerId });
        setOpen(false);
        setStep('intro');
        onEnrolled();
      } catch (enrollError: unknown) {
        setError(enrollError instanceof Error ? enrollError.message : 'Failed to save ACH enrollment');
      } finally {
        setLoading(false);
      }
    },
    [onEnrolled, storeId],
  );

  const { ready, error: dwollaError } = useDwollaWeb({
    environment: enrollment.dwolla_environment,
    tokenUrl,
    onSuccess: (result) => {
      void handleSuccess(result as DwollaSuccessResult);
    },
    onError: () => {
      setError('Dwolla enrollment failed. Check credentials and try again.');
    },
  });

  useEffect(() => {
    if (dwollaError) {
      setError('Dwolla components failed to initialize.');
    }
  }, [dwollaError]);

  async function handleSandboxEnroll() {
    setLoading(true);
    setError(null);

    try {
      await enrollStoreAch(storeId, { sandbox: true });
      onEnrolled();
    } catch (enrollError: unknown) {
      setError(enrollError instanceof Error ? enrollError.message : 'Sandbox enrollment failed');
    } finally {
      setLoading(false);
    }
  }

  if (enrollment.dwolla_mode === 'sandbox') {
    return (
      <div className="flex flex-col gap-2">
        <Button size="sm" disabled={loading} onClick={() => void handleSandboxEnroll()}>
          {loading ? 'Enrolling…' : 'Enroll sandbox ACH customer'}
        </Button>
        {error ? <Alert variant="error">{error}</Alert> : null}
      </div>
    );
  }

  return (
    <div className="flex flex-col gap-3">
      <Button size="sm" disabled={!ready || loading} onClick={() => setOpen(true)}>
        Begin ACH enrollment
      </Button>

      {open ? (
        <div className="rounded-lg border border-border bg-surface p-4">
          {step === 'intro' ? (
            <div className="space-y-3 text-sm">
              <p>
                To comply with U.S. PATRIOT Act requirements, Dwolla may ask for business formation documents,
                EIN, and controller identity information during enrollment.
              </p>
              <p className="text-muted">
                By continuing you agree to the{' '}
                <ExternalTextLink href={enrollment.terms_url} plain target="_blank">
                  Dwolla terms
                </ExternalTextLink>{' '}
                and{' '}
                <ExternalTextLink href={enrollment.privacy_url} plain target="_blank">
                  privacy policy
                </ExternalTextLink>.
              </p>
              <div className="flex gap-2">
                <Button size="sm" onClick={() => setStep('kyc')}>
                  Continue to KYC
                </Button>
                <Button size="sm" variant="secondary" onClick={() => setOpen(false)}>
                  Cancel
                </Button>
              </div>
            </div>
          ) : (
            <div>
              <DwollaBusinessVCR
                customerId={enrollment.customer?.external_customer_id}
                terms={enrollment.terms_url}
                privacy={enrollment.privacy_url}
                hideDBAField
              />
              <Button className="mt-4" size="sm" variant="secondary" onClick={() => setOpen(false)}>
                Close
              </Button>
            </div>
          )}
        </div>
      ) : null}

      {error ? <Alert variant="error">{error}</Alert> : null}
    </div>
  );
}
