import { Fragment, useCallback, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { DripStepsEditor } from '@/components/drips/DripStepsEditor';
import { PageHeader } from '@/components/ui/PageHeader';
import { Card } from '@/components/ui/Card';
import { Alert } from '@/components/ui/Alert';
import { LoadingState } from '@/components/ui/LoadingState';
import { Button } from '@/components/ui/Button';
import {
  createDripCampaign,
  deleteDripCampaign,
  fetchDripCampaignSchema,
  fetchDripCampaigns,
  updateDripCampaign,
  type DripCampaign,
  type DripCampaignSchema,
  type DripStep,
} from '@/lib/api/drips';
import { useAuth } from '@/providers/AuthProvider';

interface CampaignDraft {
  name: string;
  description: string;
  status: DripCampaign['status'];
  trigger_event: string;
  steps: DripStep[];
}

function campaignToDraft(campaign: DripCampaign): CampaignDraft {
  return {
    name: campaign.name,
    description: campaign.description ?? '',
    status: campaign.status,
    trigger_event: campaign.trigger_event ?? 'lead_created',
    steps: (campaign.steps ?? []).map((step) => ({
      ...step,
      subject: step.subject ?? '',
    })),
  };
}

function emptyDraft(): CampaignDraft {
  return {
    name: '',
    description: '',
    status: 'draft',
    trigger_event: 'lead_created',
    steps: [
      {
        sort_order: 1,
        delay_days: 0,
        delay_hours: 0,
        channel: 'email',
        subject: '',
        body_template: '',
        status: 'active',
      },
    ],
  };
}

function statusLabel(status: string): string {
  return status.charAt(0).toUpperCase() + status.slice(1);
}

export function DripCampaignsAdminPage() {
  const { user } = useAuth();
  const canManage = user?.permissions.includes('settings.manage') ?? false;
  const [campaigns, setCampaigns] = useState<DripCampaign[]>([]);
  const [schema, setSchema] = useState<DripCampaignSchema | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [expandedId, setExpandedId] = useState<number | 'new' | null>(null);
  const [editingId, setEditingId] = useState<number | 'new' | null>(null);
  const [draft, setDraft] = useState<CampaignDraft | null>(null);
  const [saving, setSaving] = useState(false);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      setCampaigns(await fetchDripCampaigns());
    } catch (loadError: unknown) {
      setError(loadError instanceof Error ? loadError.message : 'Failed to load drip campaigns');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (!canManage) {
      return;
    }

    void fetchDripCampaignSchema()
      .then(setSchema)
      .catch((loadError: unknown) => {
        setError(loadError instanceof Error ? loadError.message : 'Failed to load drip schema');
      });
  }, [canManage]);

  useEffect(() => {
    if (canManage) {
      void load();
    }
  }, [canManage, load]);

  const activeCount = useMemo(
    () => campaigns.filter((campaign) => campaign.status === 'active').length,
    [campaigns],
  );

  function startCreating() {
    setExpandedId('new');
    setEditingId('new');
    setDraft(emptyDraft());
    setSuccess(null);
  }

  function startEditing(campaign: DripCampaign) {
    setExpandedId(campaign.id);
    setEditingId(campaign.id);
    setDraft(campaignToDraft(campaign));
    setSuccess(null);
  }

  function cancelEditing() {
    setEditingId(null);
    setDraft(null);

    if (expandedId === 'new') {
      setExpandedId(null);
    }
  }

  async function saveDraft() {
    if (!draft) {
      return;
    }

    setSaving(true);
    setError(null);
    setSuccess(null);

    const payload = {
      name: draft.name.trim(),
      description: draft.description.trim() || null,
      status: draft.status,
      trigger_event: draft.trigger_event,
      steps: draft.steps.map((step, index) => ({
        ...step,
        sort_order: index + 1,
        subject: step.channel === 'email' ? step.subject?.trim() || null : null,
      })),
    };

    try {
      if (editingId === 'new') {
        const created = await createDripCampaign(payload);
        setCampaigns((current) => [...current, created].sort((a, b) => a.name.localeCompare(b.name)));
        setExpandedId(created.id);
        setEditingId(null);
        setDraft(null);
        setSuccess(`Created campaign “${created.name}”.`);
      } else if (typeof editingId === 'number') {
        const updated = await updateDripCampaign(editingId, payload);
        setCampaigns((current) =>
          current.map((row) => (row.id === updated.id ? updated : row)).sort((a, b) => a.name.localeCompare(b.name)),
        );
        setEditingId(null);
        setDraft(null);
        setSuccess(`Saved “${updated.name}”.`);
      }
    } catch (saveError: unknown) {
      setError(saveError instanceof Error ? saveError.message : 'Failed to save campaign');
    } finally {
      setSaving(false);
    }
  }

  async function handleDelete(campaign: DripCampaign) {
    setSaving(true);
    setError(null);
    setSuccess(null);

    try {
      await deleteDripCampaign(campaign.id);
      await load();
      setExpandedId(null);
      setEditingId(null);
      setDraft(null);
      setSuccess(
        (campaign.enrollments_count ?? 0) > 0
          ? `Paused “${campaign.name}” because leads are enrolled.`
          : `Deleted “${campaign.name}”.`,
      );
    } catch (deleteError: unknown) {
      setError(deleteError instanceof Error ? deleteError.message : 'Failed to remove campaign');
    } finally {
      setSaving(false);
    }
  }

  if (!canManage) {
    return (
      <>
        <PageHeader title="Drip sequences" description="Admin access required." />
        <Alert variant="error">You do not have permission to manage drip campaigns.</Alert>
      </>
    );
  }

  return (
    <>
      <PageHeader
        title="Drip sequences"
        description="Manage automated email and SMS follow-ups for new and re-engaged leads."
      />

      <div className="mb-4 flex flex-wrap items-center gap-3">
        <Link to="/settings" className="text-sm text-link hover:underline">
          ← Settings
        </Link>
      </div>

      {error ? <Alert variant="error" className="mb-4">{error}</Alert> : null}
      {success ? <Alert variant="success" className="mb-4">{success}</Alert> : null}

      <Card className="mb-4">
        <div className="flex flex-wrap items-end justify-between gap-3">
          <div>
            <p className="text-sm text-muted">
              {activeCount} active · {campaigns.length} total
            </p>
          </div>
          <div className="flex gap-2">
            <Button size="sm" variant="secondary" onClick={() => void load()} disabled={loading}>
              Refresh
            </Button>
            <Button size="sm" onClick={startCreating} disabled={editingId !== null}>
              New campaign
            </Button>
          </div>
        </div>
      </Card>

      {loading ? <LoadingState label="Loading drip campaigns…" /> : null}

      {!loading ? (
        <div className="overflow-x-auto rounded-xl border border-border bg-surface">
          <table className="w-full min-w-[960px] text-left text-sm">
            <thead>
              <tr className="border-b border-border text-muted">
                <th className="px-4 py-3 font-medium">Campaign</th>
                <th className="px-4 py-3 font-medium">Trigger</th>
                <th className="px-4 py-3 font-medium">Status</th>
                <th className="px-4 py-3 font-medium">Steps</th>
                <th className="px-4 py-3 font-medium">Enrolled</th>
                <th className="px-4 py-3 font-medium" />
              </tr>
            </thead>
            <tbody>
              {expandedId === 'new' && draft && schema ? (
                <tr className="border-b border-border bg-surface-muted/80">
                  <td colSpan={6} className="px-4 py-4">
                    {renderEditor()}
                  </td>
                </tr>
              ) : null}

              {campaigns.map((campaign) => {
                const expanded = expandedId === campaign.id;

                return (
                  <Fragment key={campaign.id}>
                    <tr className="border-b border-border/70 align-top">
                      <td className="px-4 py-3">
                        <div className="font-medium text-foreground">{campaign.name}</div>
                        {campaign.slug ? <div className="mt-1 text-xs text-muted">{campaign.slug}</div> : null}
                      </td>
                      <td className="px-4 py-3">{campaign.trigger_event ?? '—'}</td>
                      <td className="px-4 py-3">{statusLabel(campaign.status)}</td>
                      <td className="px-4 py-3">{campaign.steps_count ?? campaign.steps?.length ?? 0}</td>
                      <td className="px-4 py-3">{campaign.enrollments_count ?? 0}</td>
                      <td className="px-4 py-3">
                        <div className="flex gap-2">
                          <Button
                            size="sm"
                            variant="secondary"
                            onClick={() => {
                              if (expanded) {
                                setExpandedId(null);
                                cancelEditing();
                              } else {
                                setExpandedId(campaign.id);
                              }
                            }}
                          >
                            {expanded ? 'Hide' : 'Details'}
                          </Button>
                          <Button
                            size="sm"
                            variant="secondary"
                            onClick={() =>
                              editingId === campaign.id ? cancelEditing() : startEditing(campaign)
                            }
                          >
                            {editingId === campaign.id ? 'Cancel edit' : 'Edit'}
                          </Button>
                        </div>
                      </td>
                    </tr>
                    {expanded && expandedId !== 'new' ? (
                      <tr className="border-b border-border bg-surface-muted/80">
                        <td colSpan={6} className="px-4 py-4">
                          {editingId === campaign.id && draft && schema
                            ? renderEditor(campaign)
                            : renderDetails(campaign)}
                        </td>
                      </tr>
                    ) : null}
                  </Fragment>
                );
              })}
            </tbody>
          </table>

          {campaigns.length === 0 && expandedId !== 'new' ? (
            <p className="px-4 py-8 text-center text-sm text-muted">No drip campaigns yet.</p>
          ) : null}
        </div>
      ) : null}
    </>
  );

  function renderEditor(campaign?: DripCampaign) {
    if (!draft || !schema) {
      return null;
    }

    return (
      <div className="grid gap-4">
        <label className="flex flex-col gap-1 text-sm">
          <span className="font-medium text-foreground">Name</span>
          <input
            className="rounded-lg border border-border bg-surface text-foreground px-3 py-2"
            value={draft.name}
            onChange={(event) => setDraft({ ...draft, name: event.target.value })}
          />
        </label>

        <label className="flex flex-col gap-1 text-sm">
          <span className="font-medium text-foreground">Description</span>
          <textarea
            className="min-h-20 rounded-lg border border-border bg-surface text-foreground px-3 py-2"
            value={draft.description}
            onChange={(event) => setDraft({ ...draft, description: event.target.value })}
          />
        </label>

        <div className="grid gap-3 md:grid-cols-2">
          <label className="flex flex-col gap-1 text-sm">
            <span className="font-medium text-foreground">Trigger event</span>
            <select
              className="rounded-lg border border-border bg-surface text-foreground px-3 py-2"
              value={draft.trigger_event}
              onChange={(event) => setDraft({ ...draft, trigger_event: event.target.value })}
            >
              {schema.trigger_events.map((trigger) => (
                <option key={trigger.value} value={trigger.value}>
                  {trigger.label}
                </option>
              ))}
            </select>
          </label>

          <label className="flex flex-col gap-1 text-sm">
            <span className="font-medium text-foreground">Status</span>
            <select
              className="rounded-lg border border-border bg-surface text-foreground px-3 py-2"
              value={draft.status}
              onChange={(event) =>
                setDraft({ ...draft, status: event.target.value as DripCampaign['status'] })
              }
            >
              {schema.statuses.map((status) => (
                <option key={status.value} value={status.value}>
                  {status.label}
                </option>
              ))}
            </select>
          </label>
        </div>

        <DripStepsEditor
          steps={draft.steps}
          schema={schema}
          onChange={(steps) => setDraft({ ...draft, steps })}
        />

        <div className="flex flex-wrap gap-2">
          <Button size="sm" onClick={() => void saveDraft()} disabled={saving || draft.name.trim() === ''}>
            {saving ? 'Saving…' : 'Save campaign'}
          </Button>
          <Button size="sm" variant="secondary" onClick={cancelEditing} disabled={saving}>
            Cancel
          </Button>
          {campaign ? (
            <Button
              size="sm"
              variant="secondary"
              onClick={() => void handleDelete(campaign)}
              disabled={saving}
            >
              {(campaign.enrollments_count ?? 0) > 0 ? 'Pause campaign' : 'Delete campaign'}
            </Button>
          ) : null}
        </div>
      </div>
    );
  }

  function renderDetails(campaign: DripCampaign) {
    return (
      <div className="space-y-4">
        {campaign.description ? <p className="text-sm text-muted">{campaign.description}</p> : null}
        <div>
          <h3 className="text-sm font-medium text-foreground">Steps</h3>
          <ol className="mt-2 space-y-2">
            {(campaign.steps ?? []).map((step) => (
              <li key={step.id ?? step.sort_order} className="rounded-lg border border-border bg-surface text-foreground p-3 text-sm">
                <div className="font-medium">
                  Step {step.sort_order}: {step.channel.toUpperCase()}
                  {step.delay_days || step.delay_hours
                    ? ` · +${step.delay_days}d ${step.delay_hours}h`
                    : ' · immediate'}
                </div>
                {step.subject ? <div className="mt-1 text-muted">{step.subject}</div> : null}
                <div className="mt-2 whitespace-pre-wrap text-muted">{step.body_template}</div>
              </li>
            ))}
          </ol>
        </div>
      </div>
    );
  }
}
