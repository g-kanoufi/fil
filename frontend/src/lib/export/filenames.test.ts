import { describe, expect, it } from 'vitest';
import {
  activityFeedExportFilename,
  buildExportFilename,
  gridExportFilename,
  subjectActivityExportFilename,
} from './filenames';

const fixedDate = new Date('2026-05-28T15:00:00.000Z');

describe('export filenames', () => {
  it('builds dated fil-prefixed names', () => {
    expect(buildExportFilename(['leads'], fixedDate)).toBe('fil-leads-2026-05-28.csv');
  });

  it('includes grid filter slugs', () => {
    expect(gridExportFilename('leads', 'lead_status', 'leads_active', fixedDate)).toBe(
      'fil-leads-lead-status-leads-active-2026-05-28.csv',
    );
  });

  it('builds activity feed and subject names', () => {
    expect(activityFeedExportFilename(30, fixedDate)).toBe('fil-activity-30d-2026-05-28.csv');
    expect(subjectActivityExportFilename('lead', 42, fixedDate)).toBe(
      'fil-lead-42-activity-2026-05-28.csv',
    );
  });
});
