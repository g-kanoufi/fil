import type { ActivityItem } from '@/lib/api/activity';
import { buildCsv, downloadCsv } from '@/lib/export/csv';

function formatRow(item: ActivityItem): string[] {
  return [
    item.occurred_at ?? '',
    item.category,
    item.action,
    item.summary,
    item.actor.name,
    item.subject?.type ?? '',
    item.subject?.label ?? '',
    item.source,
  ];
}

const HEADERS = [
  'occurred_at',
  'category',
  'action',
  'summary',
  'actor',
  'subject_type',
  'subject_label',
  'source',
];

export function buildActivityCsv(items: ActivityItem[]): string {
  return buildCsv(
    HEADERS,
    items.map((item) => formatRow(item)),
  );
}

export function downloadActivityCsv(items: ActivityItem[], filename: string): void {
  if (items.length === 0) {
    return;
  }

  downloadCsv(buildActivityCsv(items), filename);
}
