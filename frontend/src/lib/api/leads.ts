import { apiGet, apiPatch, apiPost } from './client';
import type { Store } from './stores';

export interface Lead {
  id: number;
  title: string;
  slug: string | null;
  pipeline_phase: number;
  pipeline_phase_label?: string;
  application_status?: string;
  application_status_label?: string;
  lead_status: string | null;
  lead_stage: string | null;
  lead_fdd_status: string | null;
  lead_temp: string | null;
  lead_source: string | null;
  likelihood_to_close: string | null;
  fdd_signed_at: string | null;
  disclosed_at?: string | null;
  owner_user_id: number | null;
  area_id: number | null;
  organization_id: number | null;
  status: string;
  prospect?: { id: number; name: string; email: string | null; phone: string | null } | null;
  created_at: string | null;
  updated_at: string | null;
  custom?: Record<string, unknown>;
}

export interface LeadPhaseEvent {
  id: number;
  from_phase: number | null;
  to_phase: number;
  actor_user_id: number | null;
  created_at: string | null;
}

export interface LeadWithEvents extends Lead {
  phase_events?: LeadPhaseEvent[];
}

export interface LeadsResponse {
  data: Lead[];
}

export interface LeadResponse {
  data: Lead;
}

export function fetchLeads(): Promise<LeadsResponse> {
  return apiGet<LeadsResponse>('/v1/leads');
}

export function fetchLead(id: number): Promise<LeadResponse> {
  return apiGet<LeadResponse>(`/v1/leads/${id}`);
}

export function createLead(payload: {
  title: string;
  lead_source?: string;
  lead_status?: string;
  area_id?: number | null;
  custom?: Record<string, unknown>;
}): Promise<LeadResponse> {
  return apiPost<LeadResponse>('/v1/leads', payload);
}

export function updateLead(
  id: number,
  payload: Partial<Lead> & { custom?: Record<string, unknown> },
): Promise<LeadResponse> {
  return apiPatch<LeadResponse>(`/v1/leads/${id}`, payload);
}

export function convertLeadToStore(id: number): Promise<{ data: Store }> {
  return apiPost<{ data: Store }>(`/v1/leads/${id}/convert`, {});
}

export function transitionLeadPhase(
  id: number,
  toPhase: number,
  meta?: Record<string, unknown>,
): Promise<LeadResponse> {
  return apiPost<LeadResponse>(`/v1/leads/${id}/transition-phase`, {
    to_phase: toPhase,
    meta,
  });
}
