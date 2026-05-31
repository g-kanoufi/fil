import { FormEvent, useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { TextLink } from '@/components/ui/TextLink';
import { DetailPanelChrome } from '@/components/ui/SlideOver';
import type { EntityDetailPageProps } from '@/components/detail/types';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card, CardHeader } from '@/components/ui/Card';
import { DataTable } from '@/components/ui/DataTable';
import { EmptyState } from '@/components/ui/EmptyState';
import { EntityLoadState } from '@/components/ui/EntityLoadState';
import { FormField } from '@/components/ui/FormField';
import { PageHeader } from '@/components/ui/PageHeader';
import { StoreAchEnrollmentCard } from '@/components/ach/StoreAchEnrollmentCard';
import { StoreEditForm } from '@/components/stores/StoreEditForm';
import { EntityActivityTimeline } from '@/components/activity/EntityActivityTimeline';
import { EntityCustomFieldsPanel } from '@/components/fields/EntityCustomFieldsPanel';
import {
  calculateRoyalties,
  fetchRoyaltyPeriods,
  fetchStore,
  fetchStoreOwners,
  triggerAchTransfer,
  updateStore,
  type RoyaltyPeriod,
  type Store,
  type StoreOwner,
} from '@/lib/api/stores';
import { useAuth } from '@/providers/AuthProvider';

export function StoreDetailPage({
  recordId: recordIdProp,
  layout = 'page',
  onPanelClose,
  fullPagePath,
}: EntityDetailPageProps = {}) {
  const { can, isUiDisabled } = useAuth();
  const { id } = useParams<{ id: string }>();
  const storeId = recordIdProp ?? Number(id);
  const panelMode = layout === 'panel';
  const [store, setStore] = useState<Store | null>(null);
  const [owners, setOwners] = useState<StoreOwner[]>([]);
  const [periods, setPeriods] = useState<RoyaltyPeriod[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [calculating, setCalculating] = useState(false);
  const [triggeringPeriodId, setTriggeringPeriodId] = useState<number | null>(null);
  const [triggeredPeriodIds, setTriggeredPeriodIds] = useState<Set<number>>(new Set());
  const [status, setStatus] = useState<string | null>(null);
  const [revenue, setRevenue] = useState('10000');
  const canEditStore = can('stores.manage');
  const showAch = !isUiDisabled('nav_stores', 'nav_stores_ach');
  const showRoyalties = !isUiDisabled('nav_stores', 'nav_stores_royalties');
  const canViewRoyalties = can('royalties.view') && showRoyalties;

  useEffect(() => {
    if (!Number.isFinite(storeId)) {
      setError('Invalid store id');
      setLoading(false);
      return;
    }

    let cancelled = false;

    const requests: [
      Promise<Store>,
      Promise<StoreOwner[]>,
      Promise<RoyaltyPeriod[]>?,
    ] = [fetchStore(storeId), fetchStoreOwners(storeId)];

    if (canViewRoyalties) {
      requests.push(fetchRoyaltyPeriods(storeId));
    }

    void Promise.all(requests)
      .then((results) => {
        if (!cancelled) {
          const [storeData, ownerData, periodData = []] = results;
          setStore(storeData);
          setOwners(ownerData);
          setPeriods(periodData);
        }
      })
      .catch((loadError: unknown) => {
        if (!cancelled) {
          setError(loadError instanceof Error ? loadError.message : 'Failed to load store');
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
  }, [storeId, canViewRoyalties]);

  async function onCalculate(event: FormEvent) {
    event.preventDefault();

    if (!store) {
      return;
    }

    setCalculating(true);
    setError(null);

    try {
      const period = await calculateRoyalties(store.id, {
        gross_revenue: Number(revenue),
        order_count: 100,
      });
      setPeriods((current) => [period, ...current]);
    } catch (calcError: unknown) {
      setError(calcError instanceof Error ? calcError.message : 'Calculation failed');
    } finally {
      setCalculating(false);
    }
  }

  async function onTriggerAch(period: RoyaltyPeriod) {
    if (!store) {
      return;
    }

    setTriggeringPeriodId(period.id);
    setStatus(null);

    try {
      const transfer = await triggerAchTransfer(store.id, period.id);
      setStatus(`ACH queued (transfer #${transfer.id}, $${transfer.amount}).`);
      setTriggeredPeriodIds((prev) => new Set(prev).add(period.id));
    } catch (achError: unknown) {
      setStatus(achError instanceof Error ? achError.message : 'ACH trigger failed');
    } finally {
      setTriggeringPeriodId(null);
    }
  }

  if (loading || !store) {
    return (
      <EntityLoadState
        loading={loading}
        found={store != null}
        error={error}
        loadingLabel="Loading store…"
        notFoundMessage="Store not found"
        layout={panelMode ? 'panel' : 'page'}
        backTo={{ label: '← Back to stores', href: '/reports/stores' }}
      >
        {null}
      </EntityLoadState>
    );
  }

  const detailBody = (
    <>
      {error ? <Alert variant="error" className="mb-4">{error}</Alert> : null}
      {status ? <Alert variant="success" className="mb-4">{status}</Alert> : null}

      <div className={panelMode ? 'grid gap-4' : 'grid gap-6 lg:grid-cols-2'}>
        <Card>
          <CardHeader title="Store details" />
          <dl className="text-sm">
            <dt className="text-muted">Status</dt>
            <dd className="mt-1 font-medium">{store.store_status ?? store.status}</dd>
          </dl>
          <StoreEditForm
            store={store}
            canEdit={canEditStore}
            onUpdated={(updated) => setStore(updated)}
            onError={(message) => setError(message)}
          />

          <EntityCustomFieldsPanel
            entity="store"
            values={store.custom ?? {}}
            canEdit={canEditStore}
            onSave={async (custom) => {
              const updated = await updateStore(store.id, { custom });
              setStore(updated);
            }}
          />
        </Card>

        <Card>
          <CardHeader title="Owners" />
          {owners.length === 0 ? (
            <EmptyState size="compact" title="No owners linked" description="Assign franchisees or managers from store administration." />
          ) : null}
          <ul className="space-y-2 text-sm">
            {owners.map((owner) => (
              <li key={owner.id} className="flex items-center justify-between rounded-lg border border-border px-3 py-2">
                <span>{owner.user?.name ?? `User #${owner.user_id}`}</span>
                <span className="text-muted">
                  {owner.ownership_pct ? `${owner.ownership_pct}%` : ''}
                  {owner.role ? ` · ${owner.role}` : ''}
                </span>
              </li>
            ))}
          </ul>
        </Card>
      </div>

      {showAch ? <StoreAchEnrollmentCard storeId={store.id} /> : null}

      {showRoyalties ? (
      <Card className="mt-6">
        <CardHeader title="Royalties" description="Calculate periods and trigger ACH transfers." />

        <form onSubmit={onCalculate} className="mb-6 flex flex-wrap items-end gap-4">
          <FormField
            label="Gross revenue"
            id="gross"
            value={revenue}
            onChange={(event) => setRevenue(event.target.value)}
            className="w-48"
          />
          <Button type="submit" disabled={calculating} size="sm">
            {calculating ? 'Calculating…' : 'Calculate period'}
          </Button>
        </form>

        <DataTable
          columns={[
            {
              key: 'period',
              header: 'Period',
              render: (period) => `${period.period_start ?? '—'} → ${period.period_end ?? '—'}`,
            },
            {
              key: 'gross',
              header: 'Gross',
              className: 'text-right',
              render: (period) => `$${period.gross_revenue}`,
            },
            {
              key: 'royalties',
              header: 'Royalties',
              className: 'text-right',
              render: (period) => `$${period.total_royalties}`,
            },
            {
              key: 'status',
              header: 'Status',
              render: (period) => <Badge>{period.status}</Badge>,
            },
            {
              key: 'ach',
              header: 'ACH',
              render: (period) => (
                <Button
                  variant="secondary"
                  size="sm"
                  onClick={() => void onTriggerAch(period)}
                  disabled={triggeringPeriodId === period.id || triggeredPeriodIds.has(period.id)}
                >
                  {triggeringPeriodId === period.id
                    ? 'Triggering…'
                    : triggeredPeriodIds.has(period.id)
                      ? 'ACH queued'
                      : 'Trigger ACH'}
                </Button>
              ),
            },
          ]}
          rows={periods}
          rowKey={(period) => period.id}
          emptyMessage="No royalty periods yet."
        />
      </Card>
      ) : null}

      <div className={panelMode ? 'mt-4' : 'mt-6'}>
        <EntityActivityTimeline subjectType="store" subjectId={store.id} />
      </div>
    </>
  );

  if (panelMode) {
    return (
      <>
        <DetailPanelChrome
          title={store.name}
          onClose={onPanelClose ?? (() => undefined)}
          openFullPageHref={fullPagePath ?? `/reports/stores/${store.id}`}
        />
        {detailBody}
      </>
    );
  }

  return (
    <>
      <PageHeader
        title={store.name}
        breadcrumbs={
          <TextLink to="/reports/stores" plain>
            ← Stores
          </TextLink>
        }
      />
      {detailBody}
    </>
  );
}
