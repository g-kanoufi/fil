import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { Card } from '@/components/ui/Card';
import { DataTable } from '@/components/ui/DataTable';
import { LoadingState } from '@/components/ui/LoadingState';
import { PageHeader } from '@/components/ui/PageHeader';
import { fetchClosings, formatFeeCents, type Closing } from '@/lib/api/closings';

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

export function ClosingsPage() {
  const [closings, setClosings] = useState<Closing[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;

    void fetchClosings()
      .then((rows) => {
        if (!cancelled) {
          setClosings(rows);
        }
      })
      .catch((loadError: unknown) => {
        if (!cancelled) {
          setError(loadError instanceof Error ? loadError.message : 'Failed to load closings');
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
  }, []);

  if (loading) {
    return <LoadingState label="Loading closings…" />;
  }

  return (
    <>
      <PageHeader title="Closings" description="Deal closings and fee tracking." />

      {error ? <Alert variant="error" className="mb-4">{error}</Alert> : null}

      <Card>
        <DataTable
          rows={closings}
          rowKey={(row) => row.id}
          emptyMessage="No closings found."
          columns={[
            {
              key: 'title',
              header: 'Title',
              render: (row) => (
                <Link to={`/reports/closings/${row.id}`} className="text-link hover:underline">
                  {row.title}
                </Link>
              ),
            },
            {
              key: 'status',
              header: 'Status',
              render: (row) => <Badge variant={statusVariant(row.status)}>{row.status_label}</Badge>,
            },
            {
              key: 'closing_date',
              header: 'Closing date',
              render: (row) =>
                row.closing_date ? new Date(row.closing_date).toLocaleDateString() : '—',
            },
            {
              key: 'fee_total_cents',
              header: 'Fees',
              render: (row) => formatFeeCents(row.fee_total_cents),
            },
          ]}
        />
      </Card>
    </>
  );
}
