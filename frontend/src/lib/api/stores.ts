import { apiGet, apiPatch, apiPost, apiPut } from './client';

export interface Store {
  id: number;
  name: string;
  slug: string | null;
  store_status: string | null;
  status: string;
  area_id: number | null;
  buildout_started_at?: string | null;
  expected_opening_at?: string | null;
  opened_at?: string | null;
  custom?: Record<string, unknown>;
}

export interface StoreOpeningChecklistItem {
  key: string;
  label: string;
  completed_at: string | null;
  notes: string | null;
  sort_order: number;
}

export interface StoreOpeningChecklist {
  items: StoreOpeningChecklistItem[];
  timeline: {
    buildout_started_at: string | null;
    expected_opening_at: string | null;
    opened_at: string | null;
  };
}

export interface StoreOwner {
  id: number;
  user_id: number;
  ownership_pct: string | null;
  role: string | null;
  user?: { id: number; name: string; email: string };
}

export interface RoyaltyPeriod {
  id: number;
  store_id: number;
  gross_revenue: string;
  total_royalties: string;
  period_start: string | null;
  period_end: string | null;
  status: string;
}

export function fetchStore(id: number): Promise<Store> {
  return apiGet<{ data: Store }>(`/v1/stores/${id}`).then((body) => body.data);
}

export function createStore(payload: {
  name: string;
  area_id?: number | null;
  store_status?: string | null;
  custom?: Record<string, unknown>;
}): Promise<Store> {
  return apiPost<{ data: Store }>('/v1/stores', payload).then((body) => body.data);
}

export function updateStore(
  id: number,
  payload: Partial<Store> & { custom?: Record<string, unknown> },
): Promise<Store> {
  return apiPatch<{ data: Store }>(`/v1/stores/${id}`, payload).then((body) => body.data);
}

export function fetchStoreOpeningChecklist(storeId: number): Promise<StoreOpeningChecklist> {
  return apiGet<{ data: StoreOpeningChecklist }>(`/v1/stores/${storeId}/opening-checklist`).then(
    (body) => body.data,
  );
}

export function updateStoreOpeningChecklist(
  storeId: number,
  payload: {
    items?: Array<{ key: string; completed?: boolean; notes?: string | null }>;
    buildout_started_at?: string | null;
    expected_opening_at?: string | null;
    opened_at?: string | null;
  },
): Promise<StoreOpeningChecklist> {
  return apiPatch<{ data: StoreOpeningChecklist }>(`/v1/stores/${storeId}/opening-checklist`, payload).then(
    (body) => body.data,
  );
}

export function fetchStoreOwners(storeId: number): Promise<StoreOwner[]> {
  return apiGet<{ data: StoreOwner[] }>(`/v1/stores/${storeId}/owners`).then((body) => body.data);
}

export function syncStoreOwners(
  storeId: number,
  owners: Array<{ user_id: number; ownership_pct?: number; role?: string }>,
): Promise<StoreOwner[]> {
  return apiPut<{ data: StoreOwner[] }>(`/v1/stores/${storeId}/owners`, { owners }).then(
    (body) => body.data,
  );
}

export function fetchRoyaltyPeriods(storeId: number): Promise<RoyaltyPeriod[]> {
  return apiGet<{ data: RoyaltyPeriod[] }>(`/v1/stores/${storeId}/royalty-periods`).then(
    (body) => body.data,
  );
}

export function calculateRoyalties(
  storeId: number,
  payload: { gross_revenue: number; order_count?: number },
): Promise<RoyaltyPeriod> {
  return apiPost<{ data: RoyaltyPeriod }>(`/v1/stores/${storeId}/royalty-periods/calculate`, payload).then(
    (body) => body.data,
  );
}

export interface AchTransfer {
  id: number;
  store_id: number | null;
  amount: string;
  status: number;
  provider: string;
  provider_status: string | null;
}

export function triggerAchTransfer(
  storeId: number,
  periodId: number,
  amount?: number,
): Promise<AchTransfer> {
  return apiPost<{ data: AchTransfer }>(
    `/v1/stores/${storeId}/royalty-periods/${periodId}/trigger-ach`,
    amount !== undefined ? { amount } : {},
  ).then((body) => body.data);
}
