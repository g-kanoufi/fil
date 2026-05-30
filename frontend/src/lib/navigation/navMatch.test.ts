import { describe, expect, it } from 'vitest';
import { navItemMatches, pathnameMatches } from './navMatch';

describe('pathnameMatches', () => {
  it('matches dashboard only on root', () => {
    expect(pathnameMatches('/', '/')).toBe(true);
    expect(pathnameMatches('/documents', '/')).toBe(false);
    expect(pathnameMatches('/reports/leads', '/')).toBe(false);
  });

  it('matches exact and nested detail routes', () => {
    expect(pathnameMatches('/documents', '/documents')).toBe(true);
    expect(pathnameMatches('/documents/12', '/documents')).toBe(true);
    expect(pathnameMatches('/reports/stores', '/reports/stores')).toBe(true);
    expect(pathnameMatches('/reports/stores/5', '/reports/stores')).toBe(true);
  });

  it('does not match sibling routes', () => {
    expect(pathnameMatches('/documents', '/reports/stores')).toBe(false);
    expect(pathnameMatches('/reports/leads', '/reports/stores')).toBe(false);
  });
});

describe('navItemMatches', () => {
  it('matches filter query items exactly', () => {
    expect(
      navItemMatches('/reports/stores', '?filter=store_status', '/reports/stores?filter=store_status'),
    ).toBe(true);

    expect(
      navItemMatches('/reports/stores', '?filter=store_area', '/reports/stores?filter=store_status'),
    ).toBe(false);
  });

  it('normalizes subFilter values when matching', () => {
    expect(
      navItemMatches(
        '/reports/leads',
        '?filter=lead_status&subFilter=Active_Lead',
        '/reports/leads?filter=lead_status&subFilter=active_lead',
      ),
    ).toBe(true);
  });

  it('does not treat unfiltered grid route as a filter match', () => {
    expect(navItemMatches('/reports/stores', '?filter=store_status', '/reports/stores')).toBe(false);
  });

  it('does not match filter-only parent when subFilter is active', () => {
    expect(
      navItemMatches(
        '/reports/stores',
        '?filter=store_status&subFilter=open',
        '/reports/stores?filter=store_status',
      ),
    ).toBe(false);

    expect(
      navItemMatches(
        '/reports/stores',
        '?filter=store_status&subFilter=open',
        '/reports/stores?filter=store_status&subFilter=open',
      ),
    ).toBe(true);
  });
});
