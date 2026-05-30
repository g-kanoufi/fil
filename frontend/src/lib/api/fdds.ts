import { apiGet, apiPatchForm, apiPostForm } from './client';

export interface FddArea {
  id: number;
  name: string;
}

export interface FddDocument {
  id: number;
  title: string;
}

export interface Fdd {
  id: number;
  type: string;
  title: string;
  slug: string | null;
  status: string;
  area_id: number | null;
  document_id: number | null;
  version: number | null;
  deliveries_count?: number;
  area?: FddArea | null;
  document?: FddDocument | null;
}

export interface FddSummary {
  fdds: { total: number; unit: number; area: number };
  deliveries: { total: number; sent_30d: number; pending_signature: number };
}

export interface FddAvailability {
  prospect_fdd: boolean;
  prospect_area_fdd: boolean;
  area_id: number | null;
  area_name: string | null;
}

export interface FddDelivery {
  id: number;
  fdd_id: number;
  lead_id: number;
  status: string;
  sent_at: string | null;
  delivery_method?: string | null;
  document_id?: number | null;
  resend_count?: number;
  last_resent_at?: string | null;
  signature_status?: string | null;
  signed_at?: string | null;
  signed_name?: string | null;
  fdd?: Fdd;
  lead?: { id: number; title: string; lead_fdd_status: string | null };
  recipient?: { id: number; name: string; email: string };
}

export interface BulkSendResult {
  sent: Array<{
    lead_id: number;
    lead_title: string;
    delivery_id: number;
    fdd_id: number;
    fdd_title: string;
  }>;
  skipped: Array<{ lead_id: number; lead_title?: string; reason: string }>;
}

export interface Signature {
  id: number;
  status: string;
  signed_name: string | null;
  signed_at: string | null;
}

export function fetchFddSummary(): Promise<FddSummary> {
  return apiGet<{ data: FddSummary }>('/v1/fdds/summary').then((body) => body.data);
}

export function fetchFdds(): Promise<Fdd[]> {
  return apiGet<{ data: Fdd[] }>('/v1/fdds').then((body) => body.data);
}

export interface FddFormPayload {
  type: 'unit' | 'area';
  title: string;
  area_id?: number | null;
  status?: 'active' | 'inactive';
  pdf?: File | null;
}

export function createFdd(payload: FddFormPayload): Promise<Fdd> {
  const formData = new FormData();
  formData.set('type', payload.type);
  formData.set('title', payload.title);

  if (payload.area_id != null) {
    formData.set('area_id', String(payload.area_id));
  }

  if (payload.status) {
    formData.set('status', payload.status);
  }

  if (!payload.pdf) {
    throw new Error('PDF file is required when creating an FDD.');
  }

  formData.set('pdf', payload.pdf);

  return apiPostForm<{ data: Fdd }>('/v1/fdds', formData).then((body) => body.data);
}

export function updateFdd(id: number, payload: Partial<FddFormPayload>): Promise<Fdd> {
  const formData = new FormData();

  if (payload.type) {
    formData.set('type', payload.type);
  }

  if (payload.title) {
    formData.set('title', payload.title);
  }

  if (payload.area_id !== undefined) {
    if (payload.area_id === null) {
      formData.set('area_id', '');
    } else {
      formData.set('area_id', String(payload.area_id));
    }
  }

  if (payload.status) {
    formData.set('status', payload.status);
  }

  if (payload.pdf) {
    formData.set('pdf', payload.pdf);
  }

  return apiPatchForm<{ data: Fdd }>(`/v1/fdds/${id}`, formData).then((body) => body.data);
}

export function fetchFddDeliveries(limit = 50): Promise<FddDelivery[]> {
  return apiGet<{ data: FddDelivery[] }>(`/v1/fdd-deliveries?limit=${limit}`).then(
    (body) => body.data,
  );
}

export function fetchLeadFddAvailability(leadId: number): Promise<FddAvailability> {
  return apiGet<{ data: FddAvailability }>(`/v1/leads/${leadId}/fdd-availability`).then(
    (body) => body.data,
  );
}

export function fetchLeadFddDeliveries(leadId: number): Promise<FddDelivery[]> {
  return apiGet<{ data: FddDelivery[] }>(`/v1/leads/${leadId}/fdd-deliveries`).then(
    (body) => body.data,
  );
}

export function sendFddToLead(fddId: number, leadId: number): Promise<FddDelivery> {
  return apiPost<{ data: FddDelivery }>(`/v1/fdds/${fddId}/leads/${leadId}/send`, {}).then(
    (body) => body.data,
  );
}

export function bulkSendFdd(leadIds: number[], type: 'unit' | 'area'): Promise<BulkSendResult> {
  return apiPost<{ data: BulkSendResult }>('/v1/fdds/bulk-send', {
    lead_ids: leadIds,
    type,
  }).then((body) => body.data);
}

export function resendFddDelivery(deliveryId: number): Promise<FddDelivery> {
  return apiPost<{ data: FddDelivery }>(`/v1/fdd-deliveries/${deliveryId}/resend`, {}).then(
    (body) => body.data,
  );
}

export function signFddDelivery(
  deliveryId: number,
  payload: { signed_name: string; agree: boolean },
): Promise<Signature> {
  return apiPost<{ data: Signature }>(`/v1/fdd-deliveries/${deliveryId}/sign`, payload).then(
    (body) => body.data,
  );
}

export { documentDownloadUrl } from './documents';
