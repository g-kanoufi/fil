import { apiGet } from './client';

export interface DashboardStats {
  leads: {
    total: number;
    by_status: Array<{ status: string; count: number }>;
    added_monthly?: Array<{ month: string; label: string; count: number }>;
  };
  stores: {
    total: number;
  };
  fdd_deliveries: {
    sent_30d: number;
    total: number;
    sent_monthly?: Array<{ month: string; label: string; count: number }>;
  };
  recent_leads: Array<{
    id: number;
    title: string;
    status: string | null;
    temp: string | null;
    pipeline_phase: number;
    pipeline_phase_label?: string;
    updated_at: string | null;
  }>;
  pipeline: Array<{ phase: string; label?: string; count: number }>;
  pipeline_phases?: Array<{ id: number; label: string; description: string }>;
  chart_months?: number;
}

export function fetchDashboardStats(months = 6): Promise<DashboardStats> {
  const params = new URLSearchParams({ months: String(months) });

  return apiGet<{ data: DashboardStats }>(`/v1/dashboard?${params.toString()}`).then((body) => body.data);
}
