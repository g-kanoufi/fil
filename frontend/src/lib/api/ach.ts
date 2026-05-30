import { apiGet, apiPost } from './client';

export interface AchTransfer {
  id: number;
  store_id: number | null;
  store_name?: string | null;
  area_id: number | null;
  transferred_at: string | null;
  provider: string;
  provider_status: string | null;
  status: number;
  amount: string;
  royalty_name: string | null;
  description: string | null;
  external_transfer_id: string | null;
}

export function achStatusLabel(transfer: AchTransfer): string {
  if (transfer.provider_status) {
    return transfer.provider_status;
  }

  return transfer.status === 1 ? 'processed' : 'pending';
}

export function fetchAchTransfers(filters: {
  store_id?: number;
  status?: string;
} = {}): Promise<AchTransfer[]> {
  const params = new URLSearchParams();

  if (filters.store_id !== undefined) {
    params.set('store_id', String(filters.store_id));
  }

  if (filters.status) {
    params.set('status', filters.status);
  }

  const query = params.toString();

  return apiGet<{ data: AchTransfer[] }>(`/v1/ach-transfers${query ? `?${query}` : ''}`).then(
    (body) => body.data,
  );
}

export function fetchAchTransfer(id: number): Promise<AchTransfer> {
  return apiGet<{ data: AchTransfer }>(`/v1/ach-transfers/${id}`).then((body) => body.data);
}

export interface StoreAchEnrollment {
  store_id: number;
  enrolled: boolean;
  plaid_mode: 'live' | 'sandbox';
  dwolla_mode: 'live' | 'sandbox';
  dwolla_environment: 'sandbox' | 'production';
  terms_url: string;
  privacy_url: string;
  ownership_certified: boolean;
  customer: {
    id: number;
    provider: string;
    external_customer_id: string;
    status: string;
  } | null;
  funding_sources: Array<{
    id: number;
    external_funding_source_id: string;
    name: string | null;
    type: string | null;
    status: string;
    is_default: boolean;
  }>;
  plaid_pending_verification_accounts: Array<{
    id: string;
    name?: string | null;
    verification_status?: string | null;
  }>;
}

export interface PlaidLinkTokenResponse {
  store_id: number;
  mode: 'live' | 'sandbox';
  link_token: string;
  expiration: string;
}

export interface PlaidLinkCompleteResponse {
  store_id: number;
  status: 'linked' | 'pending_verification';
  funding_source?: {
    id: number;
    external_funding_source_id: string;
    name: string | null;
    status: string;
  };
  pending_account?: {
    id: string;
    name: string | null;
    verification_status: string | null;
  };
}

export function fetchStoreAchCustomer(storeId: number): Promise<StoreAchEnrollment> {
  return apiGet<{ data: StoreAchEnrollment }>(`/v1/stores/${storeId}/ach/customer`).then(
    (body) => body.data,
  );
}

export function createPlaidLinkToken(storeId: number): Promise<PlaidLinkTokenResponse> {
  return apiPost<{ data: PlaidLinkTokenResponse }>(
    `/v1/stores/${storeId}/ach/plaid/link-token`,
    {},
  ).then((body) => body.data);
}

export function completePlaidLink(
  storeId: number,
  payload: {
    sandbox?: boolean;
    public_token?: string;
    account_id?: string;
    account_name?: string | null;
    verification_status?: string | null;
  },
): Promise<PlaidLinkCompleteResponse> {
  return apiPost<{ data: PlaidLinkCompleteResponse }>(
    `/v1/stores/${storeId}/ach/plaid/complete`,
    payload,
  ).then((body) => body.data);
}

export interface AchEnrollResponse {
  store_id: number;
  enrolled: boolean;
  customer: {
    id: number;
    external_customer_id: string;
    status: string;
  };
}

export function enrollStoreAch(
  storeId: number,
  payload: { sandbox?: boolean; external_customer_id?: string },
): Promise<AchEnrollResponse> {
  return apiPost<{ data: AchEnrollResponse }>(`/v1/stores/${storeId}/ach/enroll`, payload).then(
    (body) => body.data,
  );
}

export function certifyAchOwnership(storeId: number): Promise<{ ownership_certified: boolean }> {
  return apiPost<{ data: { ownership_certified: boolean } }>(
    `/v1/stores/${storeId}/ach/dwolla/certify-ownership`,
    {},
  ).then((body) => body.data);
}
