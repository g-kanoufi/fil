import { useEffect, useState } from 'react';
import { TextLink } from '@/components/ui/TextLink';
import { Alert } from '@/components/ui/Alert';
import { Card, CardHeader } from '@/components/ui/Card';
import { FormField } from '@/components/ui/FormField';
import { LoadingState } from '@/components/ui/LoadingState';
import { PageHeader } from '@/components/ui/PageHeader';
import { RoyaltyIntelligencePanel } from '@/components/royalties/RoyaltyIntelligencePanel';
import { fetchRoyaltyIntelligence, type RoyaltyIntelligenceSummary } from '@/lib/api/royalties';
import { GridPage } from '@/pages/GridPage';

export function RoyaltiesPage() {
  const [storeId, setStoreId] = useState('');
  const [intelligence, setIntelligence] = useState<RoyaltyIntelligenceSummary | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    void fetchRoyaltyIntelligence()
      .then(setIntelligence)
      .catch((loadError: unknown) => {
        setError(loadError instanceof Error ? loadError.message : 'Failed to load royalty intelligence');
      })
      .finally(() => setLoading(false));
  }, []);

  return (
    <>
      <PageHeader
        title="Royalties"
        description="Unit vs area performance and store-level royalty operations."
      />

      {error ? <Alert variant="error" className="mb-4">{error}</Alert> : null}
      {loading ? <LoadingState label="Loading royalty intelligence…" className="mb-6" /> : null}
      {intelligence ? <RoyaltyIntelligencePanel summary={intelligence} /> : null}

      <Card className="mb-6 max-w-sm">
        <FormField
          label="Store ID"
          id="store-id"
          value={storeId}
          onChange={(event) => setStoreId(event.target.value)}
          placeholder="e.g. 1"
        />
        {Number.isFinite(Number(storeId)) && storeId !== '' ? (
          <TextLink to={`/reports/stores/${storeId}`} className="mt-3 inline-block text-sm">
            Open store #{storeId} royalties →
          </TextLink>
        ) : null}
      </Card>

      <Card padding="none">
        <div className="p-6">
          <CardHeader title="Stores" />
        </div>
        <div className="border-t border-border px-6 pb-6">
          <StoresGrid />
        </div>
      </Card>
    </>
  );
}

function StoresGrid() {
  return <GridPage title="Stores" resource="stores" embedded />;
}
