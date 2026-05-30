import { apiGet } from '@/lib/api/client';

interface PublicBrandingResponse {
  data: Record<string, unknown>;
}

export function fetchPublicBranding(): Promise<Record<string, unknown>> {
  return apiGet<PublicBrandingResponse>('/public/v1/branding').then((body) => body.data);
}
