import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { FddFormModal } from '@/components/fdd/FddFormModal';
import { FddSignModal } from '@/components/fdd/FddSignModal';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card, CardHeader } from '@/components/ui/Card';
import { LoadingState } from '@/components/ui/LoadingState';
import { PageHeader } from '@/components/ui/PageHeader';
import { PageTabs } from '@/components/ui/PageTabs';
import { surface, textLink } from '@/lib/ui/tokens';
import { TextLink } from '@/components/ui/TextLink';
import { cn } from '@/lib/cn';
import { StatCard } from '@/components/ui/StatCard';
import { useAuth } from '@/providers/AuthProvider';
import { AiSearchField, AiSearchSummary } from '@/components/search/AiSearchField';
import { useInterpretedLeadSearch } from '@/lib/grid/useInterpretedLeadSearch';
import { DocumentDownloadLink } from '@/components/documents/DocumentDownloadLink';
import {
  bulkSendFdd,
  fetchFddDeliveries,
  fetchFddSummary,
  fetchFdds,
  fetchLeadFddAvailability,
  resendFddDelivery,
  signFddDelivery,
  type Fdd,
  type FddAvailability,
  type FddDelivery,
  type FddSummary,
} from '@/lib/api/fdds';

type TabId = 'catalog' | 'send' | 'deliveries';

function deliveryStatusVariant(status: string | null | undefined): 'success' | 'info' | 'warning' {
  if (status === 'signed') {
    return 'success';
  }

  if (status === 'pending' || status === 'sent') {
    return 'warning';
  }

  return 'info';
}

export function FddPage() {
  const { can } = useAuth();
  const canManage = can('fdd.manage');

  const [tab, setTab] = useState<TabId>('catalog');
  const [summary, setSummary] = useState<FddSummary | null>(null);
  const [fdds, setFdds] = useState<Fdd[]>([]);
  const [deliveries, setDeliveries] = useState<FddDelivery[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [status, setStatus] = useState<string | null>(null);

  const {
    query: leadSearch,
    interpretation: leadSearchInterpretation,
    results: searchResults,
    handleInterpretation: handleLeadSearchInterpretation,
    clear: clearLeadSearch,
  } = useInterpretedLeadSearch({ limit: 15 });
  const [selectedLeadId, setSelectedLeadId] = useState<number | null>(null);
  const [availability, setAvailability] = useState<FddAvailability | null>(null);
  const [bulkLeadIds, setBulkLeadIds] = useState('');
  const [sendingType, setSendingType] = useState<'unit' | 'area' | null>(null);
  const [resendingId, setResendingId] = useState<number | null>(null);
  const [signDelivery, setSignDelivery] = useState<FddDelivery | null>(null);
  const [formFdd, setFormFdd] = useState<Fdd | null>(null);
  const [formOpen, setFormOpen] = useState(false);

  const reload = useCallback(async () => {
    setError(null);

    try {
      const [summaryData, fddList, deliveryList] = await Promise.all([
        fetchFddSummary(),
        fetchFdds(),
        fetchFddDeliveries(100),
      ]);

      setSummary(summaryData);
      setFdds(fddList);
      setDeliveries(deliveryList);
    } catch (loadError: unknown) {
      setError(loadError instanceof Error ? loadError.message : 'Failed to load FDD data');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void reload();
  }, [reload]);

  useEffect(() => {
    if (selectedLeadId === null) {
      setAvailability(null);

      return;
    }

    void fetchLeadFddAvailability(selectedLeadId)
      .then(setAvailability)
      .catch(() => setAvailability(null));
  }, [selectedLeadId]);

  async function onSendType(type: 'unit' | 'area', leadIds: number[]) {
    if (!canManage || leadIds.length === 0) {
      return;
    }

    setSendingType(type);
    setStatus(null);

    try {
      const result = await bulkSendFdd(leadIds, type);
      const sentCount = result.sent.length;
      const skippedCount = result.skipped.length;

      setStatus(
        sentCount > 0
          ? `Sent ${type} FDD to ${sentCount} lead${sentCount === 1 ? '' : 's'}${
              skippedCount > 0 ? ` (${skippedCount} skipped)` : ''
            }.`
          : `No ${type} FDDs sent. ${skippedCount} lead${skippedCount === 1 ? '' : 's'} skipped.`,
      );

      await reload();
    } catch (sendError: unknown) {
      setStatus(sendError instanceof Error ? sendError.message : 'Send failed');
    } finally {
      setSendingType(null);
    }
  }

  async function onResend(delivery: FddDelivery) {
    setResendingId(delivery.id);
    setStatus(null);

    try {
      await resendFddDelivery(delivery.id);
      setStatus(`Resent FDD to ${delivery.lead?.title ?? 'lead'}.`);
      await reload();
    } catch (resendError: unknown) {
      setStatus(resendError instanceof Error ? resendError.message : 'Resend failed');
    } finally {
      setResendingId(null);
    }
  }

  const parsedBulkIds = useMemo(
    () =>
      bulkLeadIds
        .split(/[\s,]+/)
        .map((value) => Number(value.trim()))
        .filter((id) => Number.isFinite(id) && id > 0),
    [bulkLeadIds],
  );

  const tabs: Array<{ id: TabId; label: string }> = [
    { id: 'catalog', label: 'FDD catalog' },
    { id: 'send', label: 'Send FDD' },
    { id: 'deliveries', label: 'Delivery history' },
  ];

  if (loading) {
    return <LoadingState label="Loading FDD manager…" />;
  }

  return (
    <>
      <PageHeader
        title="FDD manager"
        description="Send disclosure documents, track deliveries, and manage signatures."
        breadcrumbs={
          <TextLink to="/reports/leads" plain>
            ← Leads
          </TextLink>
        }
      />

      {error ? <Alert variant="error" className="mb-4">{error}</Alert> : null}
      {status ? (
        <Alert variant={status.includes('failed') || status.includes('No ') ? 'error' : 'success'} className="mb-4">
          {status}
        </Alert>
      ) : null}

      {summary ? (
        <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <StatCard label="Active FDDs" value={summary.fdds.total} hint={`${summary.fdds.unit} unit · ${summary.fdds.area} area`} accent="brand" />
          <StatCard label="Deliveries (30d)" value={summary.deliveries.sent_30d} hint={`${summary.deliveries.total} total`} />
          <StatCard label="Pending signatures" value={summary.deliveries.pending_signature} />
          <StatCard label="Catalog types" value={2} hint="Unit + Area disclosure" />
        </div>
      ) : null}

      <PageTabs
        ariaLabel="FDD manager"
        items={tabs}
        value={tab}
        onChange={(id) => setTab(id as TabId)}
      />

      {tab === 'catalog' ? (
        <div id="tabpanel-catalog" role="tabpanel" aria-labelledby="tab-catalog">
          <Card>
          <CardHeader
            title="Disclosure documents"
            description="Unit and area FDDs available for delivery. Upload PDFs and manage active catalog entries."
            actions={
              canManage ? (
                <Button
                  size="sm"
                  onClick={() => {
                    setFormFdd(null);
                    setFormOpen(true);
                  }}
                >
                  Upload FDD
                </Button>
              ) : undefined
            }
          />
          <div className="overflow-x-auto">
            <table className="w-full min-w-[820px] text-left text-sm">
              <thead>
                <tr className="border-b border-border text-muted">
                  <th className="pb-2 pr-4 font-medium">Title</th>
                  <th className="pb-2 pr-4 font-medium">Type</th>
                  <th className="pb-2 pr-4 font-medium">Area</th>
                  <th className="pb-2 pr-4 font-medium">Status</th>
                  <th className="pb-2 pr-4 font-medium">Deliveries</th>
                  <th className="pb-2 pr-4 font-medium">Document</th>
                  {canManage ? <th className="pb-2 font-medium">Actions</th> : null}
                </tr>
              </thead>
              <tbody>
                {fdds.map((fdd) => (
                  <tr key={fdd.id} className="border-b border-border/70 last:border-0">
                    <td className="py-2.5 pr-4 font-medium">{fdd.title}</td>
                    <td className="py-2.5 pr-4">
                      <Badge variant="info">{fdd.type}</Badge>
                    </td>
                    <td className="py-2.5 pr-4 text-muted">{fdd.area?.name ?? 'All areas'}</td>
                    <td className="py-2.5 pr-4">
                      <Badge variant={fdd.status === 'active' ? 'success' : 'warning'}>
                        {fdd.status}
                      </Badge>
                    </td>
                    <td className="py-2.5 pr-4">{fdd.deliveries_count ?? 0}</td>
                    <td className="py-2.5 pr-4">
                      {fdd.document_id ? (
                        <DocumentDownloadLink
                          documentId={fdd.document_id}
                          className={textLink}
                        >
                          Download PDF
                        </DocumentDownloadLink>
                      ) : (
                        <span className="text-muted">No PDF</span>
                      )}
                    </td>
                    {canManage ? (
                      <td className="py-2.5">
                        <Button
                          size="sm"
                          variant="secondary"
                          onClick={() => {
                            setFormFdd(fdd);
                            setFormOpen(true);
                          }}
                        >
                          Edit
                        </Button>
                      </td>
                    ) : null}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          {fdds.length === 0 ? <p className="text-sm text-muted">No FDDs configured yet.</p> : null}
          </Card>
        </div>
      ) : null}

      {tab === 'send' ? (
        <div id="tabpanel-send" role="tabpanel" aria-labelledby="tab-send">
        <div className="grid gap-6 lg:grid-cols-2">
          <Card>
            <CardHeader title="Send to one lead" description="Search in plain language — name, temperature, pipeline stage, and more." />
            <AiSearchField
              resource="leads"
              value={leadSearch}
              onInterpretation={handleLeadSearchInterpretation}
              label="Search leads"
              placeholder="e.g. hot lead named Smith or applications in FDD review"
            />
            {leadSearchInterpretation ? (
              <AiSearchSummary
                summary={leadSearchInterpretation.summary}
                resultCount={leadSearchInterpretation.result_count}
                source={leadSearchInterpretation.source}
                className="mt-3"
                onClear={clearLeadSearch}
              />
            ) : null}
            {searchResults.length > 0 ? (
              <ul className="mb-4 max-h-48 overflow-y-auto rounded-lg border border-border">
                {searchResults.map((lead) => (
                  <li key={lead.id}>
                    <button
                      type="button"
                      className={cn(
                        'block w-full px-3 py-2 text-left text-sm',
                        surface.listItemHover,
                        selectedLeadId === lead.id ? surface.listItemSelected : '',
                      )}
                      onClick={() => setSelectedLeadId(lead.id)}
                    >
                      #{lead.id} — {lead.title}
                    </button>
                  </li>
                ))}
              </ul>
            ) : null}

            {selectedLeadId !== null && availability ? (
              <div className="mb-4 rounded-lg border border-border bg-surface-muted p-4 text-sm">
                <p className="font-medium">Area: {availability.area_name ?? 'Not assigned'}</p>
                <p className="mt-2 flex flex-wrap gap-2">
                  <Badge variant={availability.prospect_fdd ? 'success' : 'warning'}>
                    Unit FDD {availability.prospect_fdd ? 'available' : 'unavailable'}
                  </Badge>
                  <Badge variant={availability.prospect_area_fdd ? 'success' : 'warning'}>
                    Area FDD {availability.prospect_area_fdd ? 'available' : 'unavailable'}
                  </Badge>
                </p>
              </div>
            ) : null}

            {canManage && selectedLeadId !== null ? (
              <div className="flex flex-wrap gap-2">
                <Button
                  size="sm"
                  disabled={!availability?.prospect_fdd || sendingType !== null}
                  onClick={() => void onSendType('unit', [selectedLeadId])}
                >
                  {sendingType === 'unit' ? 'Sending…' : 'Send Unit FDD'}
                </Button>
                <Button
                  size="sm"
                  variant="secondary"
                  disabled={!availability?.prospect_area_fdd || sendingType !== null}
                  onClick={() => void onSendType('area', [selectedLeadId])}
                >
                  {sendingType === 'area' ? 'Sending…' : 'Send Area FDD'}
                </Button>
              </div>
            ) : null}

            {!canManage ? <p className="text-sm text-muted">You need FDD manage permission to send.</p> : null}
          </Card>

          <Card>
            <CardHeader
              title="Bulk send"
              description="Enter lead IDs (comma or newline separated). Unavailable leads are skipped automatically."
            />
            <label htmlFor="bulk-lead-ids" className="mb-1.5 block text-sm font-medium text-foreground">
              Lead IDs
            </label>
            <textarea
              id="bulk-lead-ids"
              rows={6}
              value={bulkLeadIds}
              onChange={(event) => setBulkLeadIds(event.target.value)}
              placeholder={'101\n102\n103'}
              className="mb-4 block w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm"
            />
            <p className="mb-4 text-xs text-muted">{parsedBulkIds.length} valid ID(s) parsed</p>
            {canManage ? (
              <div className="flex flex-wrap gap-2">
                <Button
                  size="sm"
                  disabled={parsedBulkIds.length === 0 || sendingType !== null}
                  onClick={() => void onSendType('unit', parsedBulkIds)}
                >
                  {sendingType === 'unit' ? 'Sending…' : 'Bulk Send Unit FDD'}
                </Button>
                <Button
                  size="sm"
                  variant="secondary"
                  disabled={parsedBulkIds.length === 0 || sendingType !== null}
                  onClick={() => void onSendType('area', parsedBulkIds)}
                >
                  {sendingType === 'area' ? 'Sending…' : 'Bulk Send Area FDD'}
                </Button>
              </div>
            ) : null}
          </Card>
        </div>
        </div>
      ) : null}

      {tab === 'deliveries' ? (
        <div id="tabpanel-deliveries" role="tabpanel" aria-labelledby="tab-deliveries">
        <Card>
          <CardHeader title="Recent deliveries" description="Track sent FDDs, resend emails, and record signatures." />
          <div className="overflow-x-auto">
            <table className="w-full min-w-[960px] text-left text-sm">
              <thead>
                <tr className="border-b border-border text-muted">
                  <th className="pb-2 pr-4 font-medium">Lead</th>
                  <th className="pb-2 pr-4 font-medium">FDD</th>
                  <th className="pb-2 pr-4 font-medium">Sent</th>
                  <th className="pb-2 pr-4 font-medium">Status</th>
                  <th className="pb-2 pr-4 font-medium">Signature</th>
                  <th className="pb-2 font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                {deliveries.map((delivery) => (
                  <tr key={delivery.id} className="border-b border-border/70 last:border-0">
                    <td className="py-2.5 pr-4">
                      {delivery.lead_id ? (
                        <Link
                          to={`/reports/leads/${delivery.lead_id}`}
                          className={textLink}
                        >
                          {delivery.lead?.title ?? `#${delivery.lead_id}`}
                        </Link>
                      ) : (
                        '—'
                      )}
                    </td>
                    <td className="py-2.5 pr-4">
                      <Badge variant="info">{delivery.fdd?.type ?? '—'}</Badge>
                      <span className="ml-2">{delivery.fdd?.title ?? `FDD #${delivery.fdd_id}`}</span>
                    </td>
                    <td className="py-2.5 pr-4 text-muted">
                      {delivery.sent_at ? new Date(delivery.sent_at).toLocaleString() : '—'}
                      {delivery.resend_count ? (
                        <span className="ml-1 text-xs">({delivery.resend_count} resend{delivery.resend_count === 1 ? '' : 's'})</span>
                      ) : null}
                    </td>
                    <td className="py-2.5 pr-4">
                      <Badge variant="info">{delivery.status}</Badge>
                    </td>
                    <td className="py-2.5 pr-4">
                      <Badge variant={deliveryStatusVariant(delivery.signature_status)}>
                        {delivery.signature_status ?? 'none'}
                      </Badge>
                      {delivery.signed_name ? (
                        <span className="ml-2 text-xs text-muted">{delivery.signed_name}</span>
                      ) : null}
                    </td>
                    <td className="py-2.5">
                      <div className="flex flex-wrap gap-2">
                        {delivery.document_id ? (
                          <DocumentDownloadLink
                            documentId={delivery.document_id}
                            className={cn('text-sm', textLink)}
                          >
                            PDF
                          </DocumentDownloadLink>
                        ) : null}
                        {canManage ? (
                          <>
                            <Button
                              size="sm"
                              variant="secondary"
                              disabled={resendingId === delivery.id}
                              onClick={() => void onResend(delivery)}
                            >
                              {resendingId === delivery.id ? 'Resending…' : 'Resend'}
                            </Button>
                            {delivery.signature_status !== 'signed' ? (
                              <Button size="sm" variant="secondary" onClick={() => setSignDelivery(delivery)}>
                                Sign
                              </Button>
                            ) : null}
                          </>
                        ) : null}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          {deliveries.length === 0 ? <p className="text-sm text-muted">No deliveries yet.</p> : null}
        </Card>
        </div>
      ) : null}

      <FddFormModal
        open={formOpen}
        fdd={formFdd}
        onClose={() => {
          setFormOpen(false);
          setFormFdd(null);
        }}
        onSaved={async () => {
          setStatus(formFdd ? 'FDD updated.' : 'FDD created.');
          await reload();
        }}
      />

      <FddSignModal
        open={signDelivery !== null}
        deliveryLabel={signDelivery?.fdd?.title ?? signDelivery?.lead?.title ?? 'FDD delivery'}
        onClose={() => setSignDelivery(null)}
        onSubmit={async (payload) => {
          if (!signDelivery) {
            return;
          }

          await signFddDelivery(signDelivery.id, payload);
          setStatus(`Signed ${signDelivery.fdd?.title ?? 'FDD'}.`);
          setSignDelivery(null);
          await reload();
        }}
      />
    </>
  );
}
