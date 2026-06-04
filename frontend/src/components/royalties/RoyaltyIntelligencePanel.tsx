import { Link } from 'react-router-dom';
import { Card, CardHeader } from '@/components/ui/Card';
import { StatCard } from '@/components/ui/StatCard';
import { TextLink } from '@/components/ui/TextLink';
import type { RoyaltyIntelligenceSummary } from '@/lib/api/royalties';

function formatMoney(value: string): string {
  const amount = Number(value);

  return Number.isFinite(amount)
    ? amount.toLocaleString(undefined, { style: 'currency', currency: 'USD' })
    : value;
}

interface RoyaltyIntelligencePanelProps {
  summary: RoyaltyIntelligenceSummary;
}

export function RoyaltyIntelligencePanel({ summary }: RoyaltyIntelligencePanelProps) {
  return (
    <>
      <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard
          label="Unit royalties (30d)"
          value={formatMoney(summary.unit.total_royalties)}
          hint={`${summary.unit.store_count} store(s) · ${formatMoney(summary.unit.total_gross)} gross`}
          accent="brand"
        />
        <StatCard
          label="Area royalties (30d)"
          value={formatMoney(summary.area.total_royalties)}
          hint={`${summary.area.area_count} area(s)`}
          accent="success"
        />
      </div>

      <div className="mb-6 grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader title="Unit performance" description="Top stores by royalty amount." />
          <div className="overflow-x-auto">
            <table className="w-full min-w-[480px] text-left text-sm">
              <thead>
                <tr className="border-b border-border text-muted">
                  <th className="pb-2 pr-4 font-medium">Store</th>
                  <th className="pb-2 pr-4 font-medium">Gross</th>
                  <th className="pb-2 font-medium">Royalties</th>
                </tr>
              </thead>
              <tbody>
                {summary.unit.by_store.map((row) => (
                  <tr key={row.store_id} className="border-b border-border/70 last:border-0">
                    <td className="py-2.5 pr-4">
                      <TextLink to={`/reports/stores/${row.store_id}`}>{row.store_name}</TextLink>
                    </td>
                    <td className="py-2.5 pr-4 text-foreground">{formatMoney(row.gross_revenue)}</td>
                    <td className="py-2.5 text-foreground">{formatMoney(row.royalty_amount)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </Card>

        <Card>
          <CardHeader title="Area performance" description="Area fees vs underlying unit royalties." />
          <div className="overflow-x-auto">
            <table className="w-full min-w-[480px] text-left text-sm">
              <thead>
                <tr className="border-b border-border text-muted">
                  <th className="pb-2 pr-4 font-medium">Area</th>
                  <th className="pb-2 pr-4 font-medium">Unit total</th>
                  <th className="pb-2 font-medium">Area fee</th>
                </tr>
              </thead>
              <tbody>
                {summary.area.by_area.map((row) => (
                  <tr key={row.area_id} className="border-b border-border/70 last:border-0">
                    <td className="py-2.5 pr-4 text-foreground">
                      <Link to={`/reports/stores?area=${row.area_id}`} className="text-link hover:underline">
                        {row.area_name}
                      </Link>
                    </td>
                    <td className="py-2.5 pr-4 text-foreground">
                      {formatMoney(row.sum_unit_royalties)}
                    </td>
                    <td className="py-2.5 text-foreground">{formatMoney(row.amount)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </Card>
      </div>
    </>
  );
}
