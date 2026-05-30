import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { Card } from '@/components/ui/Card';
import { DataTable } from '@/components/ui/DataTable';
import { FormField } from '@/components/ui/FormField';
import { LoadingState } from '@/components/ui/LoadingState';
import { PageHeader } from '@/components/ui/PageHeader';
import { Button } from '@/components/ui/Button';
import {
  achStatusLabel,
  fetchAchTransfer,
  fetchAchTransfers,
  type AchTransfer,
} from '@/lib/api/ach';

function formatMoney(value: string | null | undefined): string {
  if (!value) {
    return '—';
  }

  const amount = Number(value);

  return Number.isFinite(amount)
    ? amount.toLocaleString(undefined, { style: 'currency', currency: 'USD' })
    : value;
}

function formatDate(value: string | null): string {
  if (!value) {
    return '—';
  }

  return new Date(value).toLocaleString();
}

export function AchPage() {
  const [transfers, setTransfers] = useState<AchTransfer[]>([]);
  const [selected, setSelected] = useState<AchTransfer | null>(null);
  const [storeFilter, setStoreFilter] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [loading, setLoading] = useState(true);
  const [detailLoading, setDetailLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      const storeId = storeFilter.trim() === '' ? undefined : Number(storeFilter);
      const rows = await fetchAchTransfers({
        store_id: Number.isFinite(storeId) ? storeId : undefined,
        status: statusFilter || undefined,
      });
      setTransfers(rows);
      setSelected(null);
    } catch (loadError: unknown) {
      setError(loadError instanceof Error ? loadError.message : 'Failed to load ACH transfers');
    } finally {
      setLoading(false);
    }
  }, [statusFilter, storeFilter]);

  useEffect(() => {
    void load();
  }, [load]);

  const totalAmount = useMemo(
    () => transfers.reduce((sum, row) => sum + (Number(row.amount) || 0), 0),
    [transfers],
  );

  async function openDetail(id: number) {
    setDetailLoading(true);
    setError(null);

    try {
      setSelected(await fetchAchTransfer(id));
    } catch (detailError: unknown) {
      setError(detailError instanceof Error ? detailError.message : 'Failed to load transfer detail');
    } finally {
      setDetailLoading(false);
    }
  }

  return (
    <>
      <PageHeader
        title="ACH transfers"
        description="Review royalty ACH debits imported from legacy Dwolla records and new sandbox triggers."
      />

      {error ? <Alert variant="error" className="mb-4">{error}</Alert> : null}

      <Card className="mb-6">
        <div className="grid gap-4 md:grid-cols-[1fr_1fr_auto] md:items-end">
          <FormField
            label="Store ID"
            id="ach-store-filter"
            value={storeFilter}
            onChange={(event) => setStoreFilter(event.target.value)}
            placeholder="All stores"
          />
          <FormField
            label="Provider status"
            id="ach-status-filter"
            value={statusFilter}
            onChange={(event) => setStatusFilter(event.target.value)}
            placeholder="e.g. processed, pending"
          />
          <Button size="sm" variant="secondary" onClick={() => void load()} disabled={loading}>
            Apply filters
          </Button>
        </div>
        <p className="mt-4 text-sm text-muted">
          {transfers.length.toLocaleString()} transfer(s) · {formatMoney(String(totalAmount))} total shown
        </p>
      </Card>

      {loading ? <LoadingState label="Loading ACH transfers…" /> : null}

      {!loading ? (
        <DataTable
          rows={transfers}
          rowKey={(row) => row.id}
          emptyMessage="No ACH transfers match these filters."
          columns={[
            {
              key: 'date',
              header: 'Transferred',
              render: (row) => formatDate(row.transferred_at),
            },
            {
              key: 'store',
              header: 'Store',
              render: (row) =>
                row.store_id ? (
                  <Link to={`/reports/stores/${row.store_id}`} className="text-link hover:underline">
                    {row.store_name ?? `#${row.store_id}`}
                  </Link>
                ) : (
                  '—'
                ),
            },
            {
              key: 'royalty',
              header: 'Royalty',
              render: (row) => row.royalty_name ?? row.description ?? '—',
            },
            {
              key: 'amount',
              header: 'Amount',
              render: (row) => formatMoney(row.amount),
              className: 'font-medium',
            },
            {
              key: 'status',
              header: 'Status',
              render: (row) => (
                <Badge variant={row.provider_status === 'processed' || row.status === 1 ? 'success' : 'default'}>
                  {achStatusLabel(row)}
                </Badge>
              ),
            },
            {
              key: 'provider',
              header: 'Provider',
              render: (row) => row.provider,
            },
            {
              key: 'actions',
              header: '',
              render: (row) => (
                <Button size="sm" variant="secondary" onClick={() => void openDetail(row.id)}>
                  Details
                </Button>
              ),
            },
          ]}
        />
      ) : null}

      {selected || detailLoading ? (
        <Card className="mt-6">
          {detailLoading ? (
            <LoadingState label="Loading transfer detail…" />
          ) : selected ? (
            <dl className="grid gap-4 text-sm md:grid-cols-2">
              <div>
                <dt className="font-medium text-foreground">Transfer ID</dt>
                <dd className="mt-1 text-muted">{selected.external_transfer_id ?? selected.id}</dd>
              </div>
              <div>
                <dt className="font-medium text-foreground">Amount</dt>
                <dd className="mt-1 text-muted">{formatMoney(selected.amount)}</dd>
              </div>
              <div>
                <dt className="font-medium text-foreground">Provider status</dt>
                <dd className="mt-1 text-muted">{achStatusLabel(selected)}</dd>
              </div>
              <div>
                <dt className="font-medium text-foreground">Transferred at</dt>
                <dd className="mt-1 text-muted">{formatDate(selected.transferred_at)}</dd>
              </div>
              <div className="md:col-span-2">
                <dt className="font-medium text-foreground">Description</dt>
                <dd className="mt-1 text-muted">{selected.description ?? '—'}</dd>
              </div>
            </dl>
          ) : null}
        </Card>
      ) : null}
    </>
  );
}
