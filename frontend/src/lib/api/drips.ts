import { apiDelete, apiGet, apiPatch, apiPost } from './client';

export interface DripStep {
  id?: number;
  drip_campaign_id?: number;
  sort_order: number;
  delay_days: number;
  delay_hours: number;
  channel: 'email' | 'sms';
  subject: string | null;
  body_template: string;
  template_id?: string | null;
  conditions?: Record<string, unknown> | null;
  status: 'active' | 'inactive';
}

export interface DripCampaign {
  id: number;
  name: string;
  slug: string | null;
  description: string | null;
  status: 'active' | 'paused' | 'draft';
  trigger_event: string | null;
  steps?: DripStep[];
  steps_count?: number;
  enrollments_count?: number;
  updated_at: string;
}

export interface DripCampaignSchema {
  trigger_events: Array<{ value: string; label: string }>;
  statuses: Array<{ value: string; label: string }>;
  channels: Array<{ value: string; label: string }>;
  step_statuses: Array<{ value: string; label: string }>;
}

export function fetchDripCampaignSchema(): Promise<DripCampaignSchema> {
  return apiGet<{ data: DripCampaignSchema }>('/v1/drip-campaigns/schema').then((body) => body.data);
}

export function fetchDripCampaigns(): Promise<DripCampaign[]> {
  return apiGet<{ data: DripCampaign[] }>('/v1/drip-campaigns').then((body) => body.data);
}

export function createDripCampaign(payload: {
  name: string;
  description?: string | null;
  status?: DripCampaign['status'];
  trigger_event?: string | null;
  steps?: DripStep[];
}): Promise<DripCampaign> {
  return apiPost<{ data: DripCampaign }>('/v1/drip-campaigns', payload).then((body) => body.data);
}

export function updateDripCampaign(
  id: number,
  payload: Partial<{
    name: string;
    description: string | null;
    status: DripCampaign['status'];
    trigger_event: string | null;
    steps: DripStep[];
  }>,
): Promise<DripCampaign> {
  return apiPatch<{ data: DripCampaign }>(`/v1/drip-campaigns/${id}`, payload).then((body) => body.data);
}

export function deleteDripCampaign(id: number): Promise<void> {
  return apiDelete(`/v1/drip-campaigns/${id}`).then(() => undefined);
}
