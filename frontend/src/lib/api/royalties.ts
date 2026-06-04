import { apiGet } from './client';

export interface RoyaltyIntelligenceSummary {
  period_days: number;
  unit: {
    store_count: number;
    total_gross: string;
    total_royalties: string;
    by_store: Array<{
      store_id: number;
      store_name: string;
      area_id: number | null;
      gross_revenue: string;
      royalty_amount: string;
    }>;
  };
  area: {
    area_count: number;
    total_royalties: string;
    by_area: Array<{
      area_id: number;
      area_name: string;
      amount: string;
      sum_unit_royalties: string;
    }>;
  };
}

export function fetchRoyaltyIntelligence(days = 30): Promise<RoyaltyIntelligenceSummary> {
  const params = new URLSearchParams({ days: String(days) });

  return apiGet<{ data: RoyaltyIntelligenceSummary }>(`/v1/royalties/intelligence?${params}`).then(
    (body) => body.data,
  );
}
