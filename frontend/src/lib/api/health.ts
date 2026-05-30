import { apiGet } from '@/lib/api/client';

export type HealthResponse = {
  status: string;
  service: string;
};

export function fetchHealth(): Promise<HealthResponse> {
  return apiGet<HealthResponse>('/health');
}
