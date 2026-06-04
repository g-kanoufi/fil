import { Link } from 'react-router-dom';
import { Badge } from '@/components/ui/Badge';
import { Card, CardHeader } from '@/components/ui/Card';
import { StatCard } from '@/components/ui/StatCard';
import { TextLink } from '@/components/ui/TextLink';
import type { DashboardStats } from '@/lib/api/dashboard';

interface FranchiseeStoreOpsPanelProps {
  storeOps: NonNullable<DashboardStats['store_ops']>;
}

export function FranchiseeStoreOpsPanel({ storeOps }: FranchiseeStoreOpsPanelProps) {
  return (
    <>
      <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <StatCard
          label="My stores"
          value={storeOps.stores.length.toLocaleString()}
          accent="neutral"
        />
        <StatCard
          label="Inspections due"
          value={storeOps.inspection_due_count.toLocaleString()}
          hint="Within the next 14 days"
          accent={storeOps.inspection_due_count > 0 ? 'brand' : 'success'}
        />
        <StatCard
          label="Opening checklist items"
          value={storeOps.checklist_incomplete_count.toLocaleString()}
          hint="Incomplete across your stores"
          accent="brand"
        />
      </div>

      <Card className="mb-6">
        <CardHeader
          title="Store operations"
          description="Read-only summary for your assigned locations."
        />
        <div className="overflow-x-auto">
          <table className="w-full min-w-[640px] text-left text-sm">
            <thead>
              <tr className="border-b border-border text-muted">
                <th className="pb-2 pr-4 font-medium">Store</th>
                <th className="pb-2 pr-4 font-medium">Status</th>
                <th className="pb-2 pr-4 font-medium">Next inspection</th>
                <th className="pb-2 pr-4 font-medium">Opened</th>
                <th className="pb-2 font-medium">Checklist</th>
              </tr>
            </thead>
            <tbody>
              {storeOps.stores.map((store) => (
                <tr key={store.id} className="border-b border-border/70 last:border-0">
                  <td className="py-2.5 pr-4">
                    <TextLink to={`/reports/stores/${store.id}`}>{store.name}</TextLink>
                  </td>
                  <td className="py-2.5 pr-4">
                    <Badge>{store.store_status ?? '—'}</Badge>
                  </td>
                  <td className="py-2.5 pr-4 text-foreground">
                    {store.next_inspection_at
                      ? new Date(store.next_inspection_at).toLocaleDateString()
                      : '—'}
                  </td>
                  <td className="py-2.5 pr-4 text-muted">
                    {store.opened_at ? new Date(store.opened_at).toLocaleDateString() : '—'}
                  </td>
                  <td className="py-2.5 text-foreground">
                    {store.checklist_open_items > 0 ? (
                      <Link to={`/reports/stores/${store.id}`} className="text-brand hover:underline">
                        {store.checklist_open_items} open
                      </Link>
                    ) : (
                      'Complete'
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </Card>
    </>
  );
}
