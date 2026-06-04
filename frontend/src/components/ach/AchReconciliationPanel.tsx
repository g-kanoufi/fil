import { Card, CardHeader } from '@/components/ui/Card';
import { StatCard } from '@/components/ui/StatCard';
import type { AchReconciliationSummary } from '@/lib/api/ach-reconciliation';

function formatMoney(value: string): string {
  const amount = Number(value);

  return Number.isFinite(amount)
    ? amount.toLocaleString(undefined, { style: 'currency', currency: 'USD' })
    : value;
}

interface AchReconciliationPanelProps {
  summary: AchReconciliationSummary;
}

export function AchReconciliationPanel({ summary }: AchReconciliationPanelProps) {
  return (
    <Card className="mb-6">
      <CardHeader
        title="Reconciliation"
        description="Unpaid royalties vs ACH transfer outcomes for your scope."
      />
      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard
          label="Unpaid line items"
          value={summary.unpaid_line_items.count.toLocaleString()}
          hint={formatMoney(summary.unpaid_line_items.total_amount)}
          accent="brand"
        />
        <StatCard
          label="Paid line items"
          value={summary.paid_line_items.count.toLocaleString()}
          hint={formatMoney(summary.paid_line_items.total_amount)}
          accent="success"
        />
        <StatCard
          label="Failed transfers"
          value={summary.failed_transfers.count.toLocaleString()}
          hint={formatMoney(summary.failed_transfers.total_amount)}
          accent="neutral"
        />
        <StatCard
          label="Unlinked transfers"
          value={summary.orphan_transfers.count.toLocaleString()}
          hint={formatMoney(summary.orphan_transfers.total_amount)}
          accent="neutral"
        />
      </div>
    </Card>
  );
}
