import { apiGet } from './client';

export interface AchReconciliationSummary {
  unpaid_line_items: { count: number; total_amount: string };
  paid_line_items: { count: number; total_amount: string };
  failed_transfers: { count: number; total_amount: string };
  orphan_transfers: { count: number; total_amount: string };
}

export function fetchAchReconciliation(): Promise<AchReconciliationSummary> {
  return apiGet<{ data: AchReconciliationSummary }>('/v1/ach-transfers/reconciliation').then(
    (body) => body.data,
  );
}
