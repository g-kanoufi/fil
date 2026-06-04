import type { NavItem } from '@/types/auth';
import { Link } from 'react-router-dom';
import { Suspense, lazy, useCallback, useEffect, useState } from 'react';
import { Card, CardHeader } from '@/components/ui/Card';
import { PageHeader } from '@/components/ui/PageHeader';
import { Badge } from '@/components/ui/Badge';
import { Alert } from '@/components/ui/Alert';
import { LoadingState } from '@/components/ui/LoadingState';
import { StatCard } from '@/components/ui/StatCard';
import { TextLink } from '@/components/ui/TextLink';
import { useAuth } from '@/providers/AuthProvider';
import { formatHealthLabel, useHealth } from '@/hooks/useHealth';
import { fetchDashboardStats, type DashboardStats } from '@/lib/api/dashboard';
import { fetchActivityFeed, type ActivityItem } from '@/lib/api/activity';
import { ActivityFeedList } from '@/components/activity/ActivityFeedList';
import { FranchiseeStoreOpsPanel } from '@/components/dashboard/FranchiseeStoreOpsPanel';
import { StaffTodosPanel } from '@/components/operations/StaffTodosPanel';
import { cn } from '@/lib/cn';
import { surface } from '@/lib/ui/tokens';

const DashboardCharts = lazy(() =>
  import('@/components/dashboard/DashboardCharts').then((mod) => ({
    default: mod.DashboardCharts,
  })),
);

export function HomePage() {
  const { user } = useAuth();
  const health = useHealth();
  const [months, setMonths] = useState(6);
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [statsError, setStatsError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [activity, setActivity] = useState<ActivityItem[]>([]);

  const loadStats = useCallback((periodMonths: number) => {
    setLoading(true);
    setStatsError(null);

    void fetchDashboardStats(periodMonths)
      .then(setStats)
      .catch((error: unknown) => {
        setStatsError(error instanceof Error ? error.message : 'Failed to load dashboard');
      })
      .finally(() => {
        setLoading(false);
      });
  }, []);

  useEffect(() => {
    loadStats(months);
    void fetchActivityFeed({ days: 7, limit: 5 })
      .then((response) => setActivity(response.data))
      .catch(() => setActivity([]));
  }, [loadStats, months]);

  const healthVariant =
    health.status === 'success' && health.data.status === 'ok'
      ? 'success'
      : health.status === 'loading'
        ? 'info'
        : 'error';

  return (
    <>
      <PageHeader
        title="Dashboard"
        description={`Signed in as ${user?.name} (${user?.primary_role?.replace(/_/g, ' ') ?? 'staff'})`}
      />

      {statsError ? <Alert variant="error" className="mb-6">{statsError}</Alert> : null}

      {loading && !stats ? <LoadingState label="Loading dashboard…" className="mb-6" /> : null}

      {stats ? (
        <>
          {stats.store_ops ? (
            <FranchiseeStoreOpsPanel storeOps={stats.store_ops} />
          ) : (
            <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
              <StatCard label="Active leads" value={stats.leads.total.toLocaleString()} accent="brand" />
              <StatCard label="Active stores" value={stats.stores.total.toLocaleString()} accent="neutral" />
              <StatCard
                label="FDD sent (30d)"
                value={stats.fdd_deliveries.sent_30d.toLocaleString()}
                hint={`${stats.fdd_deliveries.total.toLocaleString()} total deliveries`}
                accent="success"
              />
              <StatCard
                label="Pipeline stages"
                value={stats.pipeline.length}
                hint="Active leads in pipeline"
              />
            </div>
          )}

          {!stats.store_ops ? (
            <Suspense fallback={<LoadingState label="Loading charts…" className="mb-6" />}>
              <DashboardCharts stats={stats} months={months} onMonthsChange={setMonths} />
            </Suspense>
          ) : null}

          {!stats.store_ops ? (
            <>
              <Card className="mt-6">
            <CardHeader
              title="Activity history"
              description="Your team's recent actions."
              actions={
                <TextLink to="/history" className="text-sm">
                  View all →
                </TextLink>
              }
            />
            <ActivityFeedList items={activity} emptyMessage="No recent activity." />
          </Card>

          <Card className="mt-6">
            <CardHeader
              title="Recent leads"
              description="Latest activity across the pipeline."
              actions={
                <TextLink to="/reports/leads" className="text-sm">
                  View all →
                </TextLink>
              }
            />
            <div className="overflow-x-auto">
              <table className="w-full min-w-[640px] text-left text-sm">
                <thead>
                  <tr className="border-b border-border text-muted">
                    <th className="pb-2 pr-4 font-medium">Lead</th>
                    <th className="pb-2 pr-4 font-medium">Status</th>
                    <th className="pb-2 pr-4 font-medium">Pipeline</th>
                    <th className="pb-2 font-medium">Updated</th>
                  </tr>
                </thead>
                <tbody>
                  {stats.recent_leads.map((lead) => (
                    <tr key={lead.id} className="border-b border-border/70 last:border-0">
                      <td className="py-2.5 pr-4">
                        <TextLink to={`/reports/leads/${lead.id}`}>{lead.title}</TextLink>
                      </td>
                      <td className="py-2.5 pr-4">
                        <Badge>{lead.status ?? '—'}</Badge>
                      </td>
                      <td className="py-2.5 pr-4 text-foreground">{lead.pipeline_phase_label ?? lead.pipeline_phase}</td>
                      <td className="py-2.5 text-muted">
                        {lead.updated_at ? new Date(lead.updated_at).toLocaleDateString() : '—'}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </Card>
            </>
          ) : null}
        </>
      ) : null}

      <div className="mt-6">
        {!stats?.store_ops ? <StaffTodosPanel /> : null}
      </div>

      <div className="mt-6 grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader title="System status" description="API connectivity for this session." />
          <div className="flex items-center gap-3">
            <Badge variant={healthVariant} data-testid="api-status">
              API: {formatHealthLabel(health)}
            </Badge>
          </div>
        </Card>

        <Card>
          <CardHeader title="Quick links" description="Jump to your permitted modules." />
          <ul className="grid gap-2 sm:grid-cols-2">
            {user?.navigation
              .filter((item): item is NavItem => !('type' in item) && item.path !== '/')
              .slice(0, 8)
              .map((item) => (
                <li key={item.id}>
                  <Link
                    to={item.path}
                    className={cn(
                      'block rounded-lg border border-border px-4 py-3 text-sm font-medium text-foreground transition-colors',
                      surface.linkCardHover,
                    )}
                  >
                    {item.label}
                  </Link>
                </li>
              ))}
          </ul>
        </Card>
      </div>
    </>
  );
}
