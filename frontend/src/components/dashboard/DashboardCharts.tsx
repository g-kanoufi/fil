import type { ReactNode } from 'react';
import {
  Area,
  AreaChart,
  Bar,
  BarChart,
  CartesianGrid,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts';
import { Card, CardHeader } from '@/components/ui/Card';
import { useChartColors } from '@/lib/chartTheme';
import type { DashboardStats } from '@/lib/api/dashboard';

interface DashboardChartsProps {
  stats: DashboardStats;
  months: number;
  onMonthsChange: (months: number) => void;
}

function ChartCard({
  title,
  description,
  children,
}: {
  title: string;
  description?: string;
  children: ReactNode;
}) {
  return (
    <Card>
      <CardHeader title={title} description={description} />
      <div className="h-56 w-full">{children}</div>
    </Card>
  );
}

export function DashboardCharts({ stats, months, onMonthsChange }: DashboardChartsProps) {
  const colors = useChartColors();
  const tooltipStyle = {
    backgroundColor: colors.tooltipBg,
    border: `1px solid ${colors.tooltipBorder}`,
    borderRadius: 8,
    color: colors.tooltipText,
    fontSize: 12,
  };
  const axisTick = { fontSize: 12, fill: colors.tick };
  const leadsAdded = stats.leads.added_monthly ?? [];
  const fddsSent = stats.fdd_deliveries.sent_monthly ?? [];

  return (
    <>
      <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 className="text-base font-semibold text-foreground">Activity metrics</h2>
          <p className="text-sm text-muted">Pipeline trends over the selected period.</p>
        </div>
        <div className="flex items-center gap-2">
          <label htmlFor="dashboard-months" className="text-sm text-muted">
            Period
          </label>
          <select
            id="dashboard-months"
            className="rounded-lg border border-border bg-surface px-3 py-2 text-sm text-foreground"
            value={months}
            onChange={(event) => onMonthsChange(Number(event.target.value))}
          >
            {[3, 6, 12].map((value) => (
              <option key={value} value={value}>
                Last {value} months
              </option>
            ))}
          </select>
        </div>
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <ChartCard title="Leads added" description="New applications created each month.">
          <ResponsiveContainer width="100%" height="100%">
            <AreaChart data={leadsAdded} margin={{ top: 8, right: 8, left: 0, bottom: 0 }}>
              <defs>
                <linearGradient id="leadsAddedGradient" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="5%" stopColor={colors.brand} stopOpacity={0.35} />
                  <stop offset="95%" stopColor={colors.brand} stopOpacity={0.02} />
                </linearGradient>
              </defs>
              <CartesianGrid stroke={colors.grid} strokeDasharray="3 3" vertical={false} />
              <XAxis dataKey="label" tick={axisTick} axisLine={false} tickLine={false} />
              <YAxis allowDecimals={false} tick={axisTick} axisLine={false} tickLine={false} />
              <Tooltip contentStyle={tooltipStyle} />
              <Area
                type="monotone"
                dataKey="count"
                name="Leads"
                stroke={colors.brand}
                fill="url(#leadsAddedGradient)"
                strokeWidth={2}
              />
            </AreaChart>
          </ResponsiveContainer>
        </ChartCard>

        <ChartCard title="FDDs sent" description="Disclosure deliveries sent each month.">
          <ResponsiveContainer width="100%" height="100%">
            <BarChart data={fddsSent} margin={{ top: 8, right: 8, left: 0, bottom: 0 }}>
              <CartesianGrid stroke={colors.grid} strokeDasharray="3 3" vertical={false} />
              <XAxis dataKey="label" tick={axisTick} axisLine={false} tickLine={false} />
              <YAxis allowDecimals={false} tick={axisTick} axisLine={false} tickLine={false} />
              <Tooltip contentStyle={tooltipStyle} />
              <Bar dataKey="count" name="Sent" fill={colors.success} radius={[4, 4, 0, 0]} />
            </BarChart>
          </ResponsiveContainer>
        </ChartCard>

        <ChartCard title="Leads by status" description="Current active leads grouped by application status.">
          <ResponsiveContainer width="100%" height="100%">
            <BarChart
              data={stats.leads.by_status}
              layout="vertical"
              margin={{ top: 8, right: 16, left: 8, bottom: 0 }}
            >
              <CartesianGrid stroke={colors.grid} strokeDasharray="3 3" horizontal={false} />
              <XAxis type="number" allowDecimals={false} tick={axisTick} axisLine={false} tickLine={false} />
              <YAxis
                type="category"
                dataKey="status"
                width={120}
                tick={{ fontSize: 11, fill: colors.tick }}
                axisLine={false}
                tickLine={false}
              />
              <Tooltip contentStyle={tooltipStyle} />
              <Bar dataKey="count" name="Leads" fill={colors.info} radius={[0, 4, 4, 0]} />
            </BarChart>
          </ResponsiveContainer>
        </ChartCard>

        <ChartCard title="Pipeline snapshot" description="Active leads per pipeline stage.">
          <ResponsiveContainer width="100%" height="100%">
            <BarChart data={stats.pipeline} margin={{ top: 8, right: 8, left: 0, bottom: 0 }}>
              <CartesianGrid stroke={colors.grid} strokeDasharray="3 3" vertical={false} />
              <XAxis
                dataKey="label"
                tick={{ fontSize: 11, fill: colors.tick }}
                axisLine={false}
                tickLine={false}
                interval={0}
                angle={-20}
                textAnchor="end"
                height={56}
              />
              <YAxis allowDecimals={false} tick={axisTick} axisLine={false} tickLine={false} />
              <Tooltip contentStyle={tooltipStyle} />
              <Bar dataKey="count" name="Leads" fill={colors.neutral} radius={[4, 4, 0, 0]} />
            </BarChart>
          </ResponsiveContainer>
        </ChartCard>
      </div>
    </>
  );
}
