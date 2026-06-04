import { apiDelete, apiGet, apiPatch, apiPost } from '@/lib/api/client';

export interface PortalSessionUser {
  id: number;
  name: string;
  email: string;
  first_name: string | null;
  last_name: string | null;
}

export interface PortalApplicationField {
  key: string;
  name: string;
  type: string;
  required: boolean;
  choices: Array<{ value: string; label: string }> | null;
}

export interface PortalApplicationStep {
  step: number;
  label: string;
  fields: PortalApplicationField[];
}

export interface PortalApplicationSnapshot {
  lead: {
    id: number;
    title: string;
    pipeline_phase: number;
    pipeline_phase_label: string;
  };
  steps: PortalApplicationStep[];
  values: Record<string, unknown>;
}

export async function fetchPortalSession(): Promise<PortalSessionUser> {
  const response = await apiGet<{ data: PortalSessionUser }>('/portal/v1/session');

  return response.data;
}

export async function loginPortal(email: string, password: string): Promise<PortalSessionUser> {
  const response = await apiPost<{ data: PortalSessionUser }>('/portal/v1/session', { email, password });

  return response.data;
}

export async function setupPortalPassword(
  token: string,
  password: string,
  passwordConfirmation: string,
): Promise<PortalSessionUser> {
  const response = await apiPost<{ data: PortalSessionUser }>('/portal/v1/setup-password', {
    token,
    password,
    password_confirmation: passwordConfirmation,
  });

  return response.data;
}

export async function logoutPortal(): Promise<void> {
  await apiDelete('/portal/v1/session');
}

export async function fetchPortalApplication(): Promise<PortalApplicationSnapshot> {
  const response = await apiGet<{ data: PortalApplicationSnapshot }>('/portal/v1/application');

  return response.data;
}

export async function updatePortalApplication(values: Record<string, unknown>): Promise<void> {
  await apiPatch('/portal/v1/application', { values });
}

export interface PortalFddDelivery {
  id: number;
  status: string;
  sent_at: string | null;
  fdd: { id: number; title: string };
  can_sign: boolean;
}

export interface PortalFddSignSession {
  mode: 'local' | 'embedded';
  signing_url?: string;
  vendor_reference?: string;
  driver: string;
}

export async function fetchPortalFddDeliveries(): Promise<PortalFddDelivery[]> {
  const response = await apiGet<{ data: PortalFddDelivery[] }>('/portal/v1/fdd-deliveries');
  return response.data;
}

export async function beginPortalFddSignSession(deliveryId: number): Promise<PortalFddSignSession> {
  const response = await apiPost<{ data: PortalFddSignSession }>(
    `/portal/v1/fdd-deliveries/${deliveryId}/sign-session`,
    {},
  );
  return response.data;
}

export async function signPortalFddDelivery(
  deliveryId: number,
  payload: { signed_name: string; agree: boolean; vendor_reference?: string },
): Promise<void> {
  await apiPost(`/portal/v1/fdd-deliveries/${deliveryId}/sign`, payload);
}
