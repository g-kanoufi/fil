import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { ClosingFeeLinesEditor } from '@/components/closings/ClosingFeeLinesEditor';
import { TextLink } from '@/components/ui/TextLink';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card, CardHeader } from '@/components/ui/Card';
import { LoadingState } from '@/components/ui/LoadingState';
import { PageHeader } from '@/components/ui/PageHeader';
import { fetchClosing, formatFeeCents, updateClosing, type Closing } from '@/lib/api/closings';
import { useAuth } from '@/providers/AuthProvider';

function statusVariant(status: string): 'success' | 'info' | 'warning' | 'default' {
  if (status === 'completed') {
    return 'success';
  }

  if (status === 'cancelled') {
    return 'default';
  }

  if (status === 'in_review') {
    return 'warning';
  }

  return 'info';
}

export function ClosingDetailPage() {
  const { can } = useAuth();
  const canEdit = can('leads.manage');
  const { id } = useParams<{ id: string }>();
  const closingId = Number(id);
  const [closing, setClosing] = useState<Closing | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [transitioning, setTransitioning] = useState(false);
  const [savingFees, setSavingFees] = useState(false);

  useEffect(() => {
    if (!Number.isFinite(closingId)) {
      setError('Invalid closing id');
      setLoading(false);

      return;
    }

    let cancelled = false;

    void fetchClosing(closingId)
      .then((data) => {
        if (!cancelled) {
          setClosing(data);
        }
      })
      .catch((loadError: unknown) => {
        if (!cancelled) {
          setError(loadError instanceof Error ? loadError.message : 'Failed to load closing');
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
  }, [closingId]);

  async function onTransition(status: string) {
    if (!closing) {
      return;
    }

    setTransitioning(true);
    setError(null);

    try {
      setClosing(await updateClosing(closing.id, { status }));
    } catch (transitionError: unknown) {
      setError(transitionError instanceof Error ? transitionError.message : 'Status transition failed');
    } finally {
      setTransitioning(false);
    }
  }

  async function onSaveFees(feeLines: Closing['fee_lines']) {
    if (!closing) {
      return;
    }

    setSavingFees(true);
    setError(null);

    try {
      setClosing(await updateClosing(closing.id, { fee_lines: feeLines }));
    } finally {
      setSavingFees(false);
    }
  }

  if (loading) {
    return <LoadingState label="Loading closing…" />;
  }

  if (!closing) {
    return (
      <>
        <Alert variant="error">{error ?? 'Closing not found'}</Alert>
        <TextLink to="/reports/closings" plain className="mt-4 inline-block text-sm">
          ← Back to closings
        </TextLink>
      </>
    );
  }

  return (
    <>
      <PageHeader
        title={closing.title}
        breadcrumbs={
          <TextLink to="/reports/closings" plain>
            ← Closings
          </TextLink>
        }
      />

      {error ? <Alert variant="error" className="mb-4">{error}</Alert> : null}

      <Card>
        <CardHeader title="Closing overview" />
        <dl className="grid grid-cols-2 gap-4 text-sm">
          <div>
            <dt className="text-muted">Status</dt>
            <dd className="mt-1">
              <Badge variant={statusVariant(closing.status)}>{closing.status_label}</Badge>
            </dd>
          </div>
          <div>
            <dt className="text-muted">Closing date</dt>
            <dd className="mt-1 font-medium">
              {closing.closing_date ? new Date(closing.closing_date).toLocaleDateString() : '—'}
            </dd>
          </div>
          <div>
            <dt className="text-muted">Lead</dt>
            <dd className="mt-1 font-medium">
              {closing.lead_id ? (
                <TextLink to={`/reports/leads/${closing.lead_id}`}>
                  {closing.lead_title ?? `Lead #${closing.lead_id}`}
                </TextLink>
              ) : (
                '—'
              )}
            </dd>
          </div>
          <div>
            <dt className="text-muted">Store</dt>
            <dd className="mt-1 font-medium">
              {closing.store_id ? (
                <TextLink to={`/reports/stores/${closing.store_id}`}>
                  {closing.store_title ?? `Store #${closing.store_id}`}
                </TextLink>
              ) : (
                '—'
              )}
            </dd>
          </div>
          <div>
            <dt className="text-muted">Fee total</dt>
            <dd className="mt-1 font-medium">{formatFeeCents(closing.fee_total_cents)}</dd>
          </div>
          <div>
            <dt className="text-muted">Updated</dt>
            <dd className="mt-1 font-medium">
              {closing.updated_at ? new Date(closing.updated_at).toLocaleString() : '—'}
            </dd>
          </div>
        </dl>
      </Card>

      {canEdit && closing.allowed_status_transitions.length > 0 ? (
        <Card className="mt-6">
          <CardHeader title="Workflow" description="Move this closing to the next step." />
          <div className="flex flex-wrap gap-2">
            {closing.allowed_status_transitions.map((transition) => (
              <Button
                key={transition.key}
                type="button"
                variant="secondary"
                disabled={transitioning}
                onClick={() => void onTransition(transition.key)}
              >
                Mark {transition.label}
              </Button>
            ))}
          </div>
        </Card>
      ) : null}

      <Card className="mt-6">
        <CardHeader title="Fee lines" />
        <ClosingFeeLinesEditor
          lines={closing.fee_lines}
          canEdit={canEdit}
          saving={savingFees}
          onSave={onSaveFees}
        />
      </Card>
    </>
  );
}
