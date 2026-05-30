import { FormEvent, useCallback, useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { TextLink } from '@/components/ui/TextLink';
import { textLink } from '@/lib/ui/tokens';
import { DetailPanelChrome } from '@/components/ui/SlideOver';
import type { EntityDetailPageProps } from '@/components/detail/types';
import { CommunicationComposer } from '@/components/communications/CommunicationComposer';
import { EntityActivityTimeline } from '@/components/activity/EntityActivityTimeline';
import { EntityCustomFieldsPanel } from '@/components/fields/EntityCustomFieldsPanel';
import { FddSignModal } from '@/components/fdd/FddSignModal';
import { LeadEditForm } from '@/components/leads/LeadEditForm';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card, CardHeader } from '@/components/ui/Card';
import { LoadingState } from '@/components/ui/LoadingState';
import { PageHeader } from '@/components/ui/PageHeader';
import { fetchCommunications, type Communication } from '@/lib/api/communications';
import { DocumentDownloadLink } from '@/components/documents/DocumentDownloadLink';
import {
  fetchFdds,
  fetchLeadFddDeliveries,
  sendFddToLead,
  signFddDelivery,
  type Fdd,
  type FddDelivery,
} from '@/lib/api/fdds';
import { fetchLead, transitionLeadPhase, convertLeadToStore, updateLead, type Lead } from '@/lib/api/leads';
import { pipelinePhaseLabel, selectablePipelinePhases } from '@/lib/leadPipeline';
import { useAuth } from '@/providers/AuthProvider';

function deliveryStatusVariant(status: string | null | undefined): 'success' | 'info' | 'warning' {
  if (status === 'signed') {
    return 'success';
  }

  if (status === 'pending') {
    return 'warning';
  }

  return 'info';
}

export function LeadDetailPage({
  recordId: recordIdProp,
  layout = 'page',
  onPanelClose,
  fullPagePath,
}: EntityDetailPageProps = {}) {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const { appConfig, can } = useAuth();
  const leadId = recordIdProp ?? Number(id);
  const panelMode = layout === 'panel';
  const [lead, setLead] = useState<Lead | null>(null);
  const [fdds, setFdds] = useState<Fdd[]>([]);
  const [deliveries, setDeliveries] = useState<FddDelivery[]>([]);
  const [communications, setCommunications] = useState<Communication[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [transitioning, setTransitioning] = useState(false);
  const [sendingFddId, setSendingFddId] = useState<number | null>(null);
  const [status, setStatus] = useState<string | null>(null);
  const [signDelivery, setSignDelivery] = useState<FddDelivery | null>(null);
  const [converting, setConverting] = useState(false);

  const reloadCommunications = useCallback(async () => {
    if (!Number.isFinite(leadId)) {
      return;
    }

    const comms = await fetchCommunications(leadId);
    setCommunications(comms);
  }, [leadId]);

  const reloadDeliveries = useCallback(async () => {
    if (!Number.isFinite(leadId)) {
      return;
    }

    const list = await fetchLeadFddDeliveries(leadId);
    setDeliveries(list);
  }, [leadId]);

  useEffect(() => {
    if (!Number.isFinite(leadId)) {
      setError('Invalid lead id');
      setLoading(false);

      return;
    }

    let cancelled = false;

    void Promise.all([
      fetchLead(leadId),
      fetchFdds(),
      fetchCommunications(leadId),
      fetchLeadFddDeliveries(leadId),
    ])
      .then(([leadResponse, fddList, comms, deliveryList]) => {
        if (!cancelled) {
          setLead(leadResponse.data);
          setFdds(fddList);
          setCommunications(comms);
          setDeliveries(deliveryList);
        }
      })
      .catch((loadError: unknown) => {
        if (!cancelled) {
          setError(loadError instanceof Error ? loadError.message : 'Failed to load lead');
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
  }, [leadId]);

  async function onTransition(toPhase: number) {
    if (!lead) {
      return;
    }

    setTransitioning(true);
    setError(null);

    try {
      const response = await transitionLeadPhase(lead.id, toPhase);
      setLead(response.data);
    } catch (transitionError: unknown) {
      setError(transitionError instanceof Error ? transitionError.message : 'Phase transition failed');
    } finally {
      setTransitioning(false);
    }
  }

  async function onSendFdd(fdd: Fdd) {
    if (!lead) {
      return;
    }

    setSendingFddId(fdd.id);
    setStatus(null);

    try {
      await sendFddToLead(fdd.id, lead.id);
      setStatus(`Sent ${fdd.title}.`);
      await reloadDeliveries();
    } catch (sendError: unknown) {
      setStatus(sendError instanceof Error ? sendError.message : 'FDD send failed');
    } finally {
      setSendingFddId(null);
    }
  }

  async function onSignDelivery(payload: { signed_name: string; agree: boolean }) {
    if (!signDelivery) {
      return;
    }

    await signFddDelivery(signDelivery.id, payload);
    setStatus(`Signed ${signDelivery.fdd?.title ?? 'FDD delivery'}.`);
    await reloadDeliveries();

    if (lead) {
      const refreshed = await fetchLead(lead.id);
      setLead(refreshed.data);
    }
  }

  async function onConvertToStore() {
    if (!lead || !canEditLead) {
      return;
    }

    if (!window.confirm(`Convert "${lead.title}" to a store? The lead will be marked converted.`)) {
      return;
    }

    setConverting(true);
    setError(null);

    try {
      const response = await convertLeadToStore(lead.id);
      navigate(`/reports/stores/${response.data.id}`);
    } catch (convertError: unknown) {
      setError(convertError instanceof Error ? convertError.message : 'Convert failed');
    } finally {
      setConverting(false);
    }
  }

  function onSubmitTransition(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const toPhase = Number(form.get('to_phase'));

    if (Number.isFinite(toPhase)) {
      void onTransition(toPhase);
    }
  }

  if (loading) {
    return <LoadingState label="Loading lead…" />;
  }

  if (!lead) {
    if (panelMode) {
      return <Alert variant="error">{error ?? 'Lead not found'}</Alert>;
    }

    return (
      <>
        <Alert variant="error">{error ?? 'Lead not found'}</Alert>
        <TextLink to="/reports/leads" plain className="mt-4 inline-block text-sm">
          ← Back to leads
        </TextLink>
      </>
    );
  }

  const pipelinePhases = selectablePipelinePhases(appConfig);
  const canEditLead = can('leads.manage');
  const statusLabel =
    lead.application_status_label
    ?? lead.lead_fdd_status
    ?? lead.lead_status
    ?? 'New Lead';
  const pipelineLabel =
    lead.pipeline_phase_label
    ?? pipelinePhaseLabel(lead.pipeline_phase, appConfig);

  const detailBody = (
    <>
      {error ? <Alert variant="error" className="mb-4">{error}</Alert> : null}
      {status ? <Alert variant="success" className="mb-4">{status}</Alert> : null}

      <div className={panelMode ? 'grid gap-4' : 'grid gap-6 lg:grid-cols-2'}>
        <Card>
          <CardHeader title="Overview" />
          <dl className="grid grid-cols-2 gap-4 text-sm">
            <div>
              <dt className="text-muted">Pipeline</dt>
              <dd className="mt-1 font-medium">{pipelineLabel}</dd>
            </div>
            <div>
              <dt className="text-muted">Application status</dt>
              <dd className="mt-1">
                <Badge>{statusLabel}</Badge>
              </dd>
            </div>
            <div>
              <dt className="text-muted">Source</dt>
              <dd className="mt-1 font-medium">{lead.lead_source ?? '—'}</dd>
            </div>
            <div>
              <dt className="text-muted">Temperature</dt>
              <dd className="mt-1 font-medium">{lead.lead_temp ?? '—'}</dd>
            </div>
            <div>
              <dt className="text-muted">FDD signed</dt>
              <dd className="mt-1 font-medium">
                {lead.fdd_signed_at ? new Date(lead.fdd_signed_at).toLocaleString() : '—'}
              </dd>
            </div>
          </dl>

          <form onSubmit={onSubmitTransition} className="mt-6 flex flex-wrap items-end gap-3 border-t border-border pt-4">
            <div>
              <label htmlFor="to_phase" className="block text-sm font-medium text-foreground">
                Move to stage
              </label>
              <select
                id="to_phase"
                name="to_phase"
                defaultValue={String(lead.pipeline_phase)}
                disabled={transitioning}
                className="mt-1 rounded-lg border border-border bg-surface px-3 py-2 text-sm"
              >
                {pipelinePhases.map((phase) => (
                  <option key={phase.id} value={phase.id}>
                    {phase.label}
                  </option>
                ))}
              </select>
            </div>
            <Button type="submit" disabled={transitioning} size="sm">
              {transitioning ? 'Updating…' : 'Update pipeline'}
            </Button>
          </form>

          <LeadEditForm
            lead={lead}
            canEdit={canEditLead}
            onUpdated={(updated) => setLead(updated)}
            onError={(message) => setError(message)}
          />

          <EntityCustomFieldsPanel
            entity="lead"
            values={lead.custom ?? {}}
            canEdit={canEditLead}
            onSave={async (custom) => {
              const response = await updateLead(lead.id, { custom });
              setLead(response.data);
            }}
          />

          {canEditLead && lead.status !== 'converted' ? (
            <div className="mt-6 border-t border-border pt-4">
              <Button
                type="button"
                variant="secondary"
                size="sm"
                disabled={converting}
                onClick={() => void onConvertToStore()}
              >
                {converting ? 'Converting…' : 'Convert to store'}
              </Button>
            </div>
          ) : null}
        </Card>

        <Card>
          <CardHeader title="Send FDD" description="Active disclosure documents for this lead." />
          {fdds.length === 0 ? <p className="text-sm text-muted">No active FDDs configured.</p> : null}
          <ul className="space-y-2">
            {fdds.map((fdd) => (
              <li key={fdd.id}>
                <Button
                  variant="secondary"
                  size="sm"
                  onClick={() => void onSendFdd(fdd)}
                  disabled={sendingFddId === fdd.id}
                >
                  {sendingFddId === fdd.id ? 'Sending…' : `Send ${fdd.title}`}
                </Button>
              </li>
            ))}
          </ul>
        </Card>
      </div>

      <Card className="mt-6">
        <CardHeader title="FDD delivery history" description="Sent disclosures and signature status." />
        {deliveries.length === 0 ? (
          <p className="text-sm text-muted">No FDD deliveries yet.</p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full min-w-[720px] text-left text-sm">
              <thead>
                <tr className="border-b border-border text-muted">
                  <th className="pb-2 pr-4 font-medium">Document</th>
                  <th className="pb-2 pr-4 font-medium">Sent</th>
                  <th className="pb-2 pr-4 font-medium">Status</th>
                  <th className="pb-2 pr-4 font-medium">Signature</th>
                  <th className="pb-2 font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                {deliveries.map((delivery) => (
                  <tr key={delivery.id} className="border-b border-border/70 last:border-0">
                    <td className="py-2.5 pr-4 font-medium">{delivery.fdd?.title ?? `FDD #${delivery.fdd_id}`}</td>
                    <td className="py-2.5 pr-4 text-muted">
                      {delivery.sent_at ? new Date(delivery.sent_at).toLocaleString() : '—'}
                    </td>
                    <td className="py-2.5 pr-4">
                      <Badge variant="info">{delivery.status}</Badge>
                    </td>
                    <td className="py-2.5 pr-4">
                      <Badge variant={deliveryStatusVariant(delivery.signature_status)}>
                        {delivery.signature_status ?? 'none'}
                      </Badge>
                      {delivery.signed_at ? (
                        <span className="ml-2 text-xs text-muted">
                          {new Date(delivery.signed_at).toLocaleDateString()}
                        </span>
                      ) : null}
                    </td>
                    <td className="py-2.5">
                      <div className="flex flex-wrap gap-2">
                        {delivery.document_id ? (
                          <DocumentDownloadLink
                            documentId={delivery.document_id}
                            className={textLink}
                          >
                            Download
                          </DocumentDownloadLink>
                        ) : null}
                        {delivery.signature_status !== 'signed' ? (
                          <Button size="sm" variant="secondary" onClick={() => setSignDelivery(delivery)}>
                            Record signature
                          </Button>
                        ) : null}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </Card>

      <Card className="mt-6">
        <CardHeader title="Communications" />
        <CommunicationComposer
          leadId={lead.id}
          canSend={can('communications.manage')}
          prospectEmail={lead.prospect?.email}
          prospectPhone={lead.prospect?.phone}
          onSent={() => void reloadCommunications()}
        />
        {communications.length === 0 ? <p className="text-sm text-muted">No messages logged yet.</p> : null}
        <ul className="space-y-3">
          {communications.map((comm) => (
            <li key={comm.id} className="rounded-lg border border-border p-4">
              <div className="flex flex-wrap items-center gap-2 text-xs text-muted">
                <Badge variant="info">{comm.direction}</Badge>
                <span>{comm.type}</span>
                <span>{comm.sent_at ?? '—'}</span>
              </div>
              <p className="mt-2 text-sm text-foreground">{comm.message}</p>
            </li>
          ))}
        </ul>
      </Card>

      <div className="mt-6">
        <EntityActivityTimeline subjectType="lead" subjectId={lead.id} />
      </div>

      <FddSignModal
        open={signDelivery !== null}
        deliveryLabel={signDelivery?.fdd?.title ?? 'FDD delivery'}
        onClose={() => setSignDelivery(null)}
        onSubmit={onSignDelivery}
      />
    </>
  );

  if (panelMode) {
    return (
      <>
        <DetailPanelChrome
          title={lead.title}
          onClose={onPanelClose ?? (() => undefined)}
          openFullPageHref={fullPagePath ?? `/reports/leads/${lead.id}`}
        />
        {detailBody}
      </>
    );
  }

  return (
    <>
      <PageHeader
        title={lead.title}
        breadcrumbs={
          <TextLink to="/reports/leads" plain>
            ← Leads
          </TextLink>
        }
      />
      {detailBody}
    </>
  );
}
