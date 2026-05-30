import { describe, expect, it } from 'vitest';
import { buildGridFilters } from './filters';

const leadMenus = {
  menuItems: {
    lead_status: { label: 'Lead status', slug: 'lead_status' },
  },
  subMenuItems: {
    lead_status: {
      leads_active: { label: 'Active leads', slug: 'leads_active' },
      leads_awarded_deals: { label: 'Awarded deals', slug: 'leads_awarded_deals' },
      active: { label: 'Active', slug: 'active' },
      disclosed: { label: 'FDD Sent', slug: 'disclosed' },
      inactive: { label: 'Inactive', slug: 'inactive' },
    },
  },
};

describe('buildGridFilters', () => {
  it('maps leads_active to non-inactive lead_fdd_status values', () => {
    const filters = buildGridFilters(
      'leads',
      { filter: 'lead_status', subFilter: 'leads_active', search: '', sortField: 'updated_at', sortDirection: 'desc', leadtemp: '' },
      leadMenus,
    );

    expect(filters.lead_fdd_status).toEqual(
      expect.arrayContaining(['Active', 'active', 'disclosed', 'FDD Sent']),
    );
    expect(filters.lead_fdd_status).not.toEqual(
      expect.arrayContaining(['Inactive', 'inactive', 'Active leads']),
    );
  });

  it('maps leads_awarded_deals to awarded statuses', () => {
    const filters = buildGridFilters(
      'leads',
      { filter: 'lead_status', subFilter: 'leads_awarded_deals', search: '', sortField: 'updated_at', sortDirection: 'desc', leadtemp: '' },
      leadMenus,
    );

    expect(filters.lead_fdd_status).toEqual(
      expect.arrayContaining(['award franchise', 'award-franchise']),
    );
  });

  it('maps store_area subFilter to area_id', () => {
    const filters = buildGridFilters(
      'stores',
      { filter: 'store_area', subFilter: '42', search: '', sortField: 'updated_at', sortDirection: 'desc', leadtemp: '' },
      {
        menuItems: { store_area: { label: 'Areas', slug: 'store_area' } },
        subMenuItems: {
          store_area: {
            '42': { label: 'North Region', slug: '42' },
          },
        },
      },
    );

    expect(filters.area_id).toBe('42');
  });
});
