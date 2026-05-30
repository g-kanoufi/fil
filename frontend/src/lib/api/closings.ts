import { apiGet, apiPatch } from './client';

export interface ClosingFeeLine {
  label: string;
  amount_cents: number;
}

export interface ClosingStatusTransition {
  key: string;
  label: string;
}

export interface Closing {
  id: number;
  title: string;
  lead_id: number | null;
  lead_title?: string | null;
  store_id: number | null;
  store_title?: string | null;
  area_id: number | null;
  area_title?: string | null;
  closing_date: string | null;
  status: string;
  status_label: string;
  fee_lines: ClosingFeeLine[];
  fee_total_cents: number;
  allowed_status_transitions: ClosingStatusTransition[];
  created_at: string | null;
  updated_at: string | null;
}

export function fetchClosings(): Promise<Closing[]> {
  return apiGet<{ data: Closing[] }>('/v1/closings').then((body) => body.data);
}

export function fetchClosing(id: number): Promise<Closing> {
  return apiGet<{ data: Closing }>(`/v1/closings/${id}`).then((body) => body.data);
}

export function updateClosing(
  id: number,
  payload: { status?: string; fee_lines?: ClosingFeeLine[] },
): Promise<Closing> {
  return apiPatch<{ data: Closing }>(`/v1/closings/${id}`, payload).then((body) => body.data);
}

export function formatFeeCents(cents: number): string {
  return (cents / 100).toLocaleString(undefined, { style: 'currency', currency: 'USD' });
}

export function parseDollarsToCents(value: string): number {
  const normalized = value.replace(/[^0-9.-]/g, '');
  const amount = Number(normalized);

  if (!Number.isFinite(amount)) {
    return 0;
  }

  return Math.max(0, Math.round(amount * 100));
}

export function centsToDollarInput(cents: number): string {
  return (cents / 100).toFixed(2);
}
