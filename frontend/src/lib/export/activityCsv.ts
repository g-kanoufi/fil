import type { ActivityItem } from '@/lib/api/activity';

function escapeCsv(value: string): string {
  if (value.includes(',') || value.includes('"') || value.includes('\n')) {
    return `"${value.replace(/"/g, '""')}"`;
  }

  return value;
}

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
  const lines = [
    HEADERS.map(escapeCsv).join(','),
    ...items.map((item) => formatRow(item).map(escapeCsv).join(',')),
  ];

  return lines.join('\n');
}

export function downloadActivityCsv(items: ActivityItem[], filename: string): void {
  if (items.length === 0) {
    return;
  }

  const blob = new Blob([buildActivityCsv(items)], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  link.click();
  URL.revokeObjectURL(url);
}
