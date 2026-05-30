import { describe, expect, it } from 'vitest';
import { buildActivityCsv } from './activityCsv';
import type { ActivityItem } from '@/lib/api/activity';

const sample: ActivityItem = {
  id: '1',
  occurred_at: '2026-05-30T12:00:00Z',
  actor: { id: 1, name: 'Admin User' },
  category: 'lead',
  action: 'updated',
  summary: 'Changed phase to 2',
  subject: { type: 'lead', id: 10, label: 'Acme Lead', path: '/reports/leads/10' },
  source: 'audit',
};

describe('buildActivityCsv', () => {
  it('includes headers and escaped values', () => {
    const csv = buildActivityCsv([sample]);

    expect(csv.split('\n')[0]).toBe(
      'occurred_at,category,action,summary,actor,subject_type,subject_label,source',
    );
    expect(csv).toContain('Acme Lead');
    expect(csv).toContain('Admin User');
  });

  it('escapes commas in summary', () => {
    const csv = buildActivityCsv([
      { ...sample, summary: 'Note: moved, reassigned' },
    ]);

    expect(csv).toContain('"Note: moved, reassigned"');
  });
});
