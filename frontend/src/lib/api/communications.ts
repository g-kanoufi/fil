import { apiGet, apiPost } from './client';

export interface Communication {
  id: number;
  lead_id: number | null;
  type: string;
  direction: string;
  message: string;
  provider: string | null;
  sent_at: string | null;
  status: string;
  recipient_name: string | null;
}

export interface SendCommunicationPayload {
  lead_id: number;
  channel: 'email' | 'sms';
  message: string;
  subject?: string;
}

export function fetchCommunications(leadId?: number): Promise<Communication[]> {
  const query = leadId ? `?lead_id=${leadId}` : '';

  return apiGet<{ data: Communication[] }>(`/v1/communications${query}`).then((body) => body.data);
}

export function sendCommunication(payload: SendCommunicationPayload): Promise<Communication> {
  return apiPost<{ data: Communication }>('/v1/communications', payload).then((body) => body.data);
}
