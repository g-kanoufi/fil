import { useState } from 'react';
import { TextLink } from '@/components/ui/TextLink';
import { Card, CardHeader } from '@/components/ui/Card';
import { FormField } from '@/components/ui/FormField';
import { PageHeader } from '@/components/ui/PageHeader';
import { GridPage } from '@/pages/GridPage';

export function RoyaltiesPage() {
  const [storeId, setStoreId] = useState('');

  return (
    <>
      <PageHeader
        title="Royalties"
        description="Open a store detail page to calculate royalties and review periods."
      />

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
