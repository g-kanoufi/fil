import { apiGet } from '@/lib/api/client';
import type { AppConfig } from '@/types/auth';

interface AppConfigResponse {
  data: AppConfig;
}

export function fetchAppConfig(): Promise<AppConfig> {
  return apiGet<AppConfigResponse>('/v1/app-config').then((body) => body.data);
}
