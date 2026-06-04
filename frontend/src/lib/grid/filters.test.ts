import { describe, expect, it } from 'vitest';
import type { AppConfig } from '@/types/auth';
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

const appConfig = {
  options: {},
  menus: { top_menus: [], menus_with_columns: {} },
  lead_application_status: {
    choices: [
      {
        value: 'active',
        label: 'Active',
        slug: 'active',
        category: 'active',
        closed: false,
        pipeline_phase: null,
        sort: 20,
        filter_values: ['active', 'Active'],
      },
      {
        value: 'disclosed',
        label: 'FDD Sent',
        slug: 'disclosed',
        category: 'active',
        closed: false,
        pipeline_phase: 5,
        sort: 30,
        filter_values: ['disclosed', 'FDD Sent', 'fdd sent'],
      },
      {
        value: 'inactive',
        label: 'Inactive',
        slug: 'inactive',
        category: 'closed',
        closed: true,
        pipeline_phase: 99,
        sort: 60,
        filter_values: ['inactive', 'Inactive'],
      },
      {
        value: 'awarded_franchise',
        label: 'Award Franchise',
        slug: 'awarded_franchise',
        category: 'won',
        closed: false,
        pipeline_phase: 10,
        sort: 140,
        filter_values: ['awarded_franchise', 'Award Franchise', '14'],
      },
    ],
    groups: {
      active: {
        submenu_key: 'leads_active',
        values: ['active', 'disclosed'],
        filter_values: ['active', 'Active', 'disclosed', 'FDD Sent', 'fdd sent'],
      },
      won: {
        submenu_key: 'leads_awarded_deals',
        values: ['awarded_franchise'],
        filter_values: ['awarded_franchise', 'Award Franchise', '14'],
      },
      closed: {
        values: ['inactive'],
        filter_values: ['inactive', 'Inactive'],
      },
    },
  },
} satisfies AppConfig;

describe('buildGridFilters', () => {
  it('maps leads_active to active group filter values from app config', () => {
    const filters = buildGridFilters(
      'leads',
      { filter: 'lead_status', subFilter: 'leads_active', search: '', sortField: 'updated_at', sortDirection: 'desc', leadtemp: '' },
      leadMenus,
      appConfig,
    );

    expect(filters.lead_status).toEqual(
      expect.arrayContaining(['active', 'disclosed']),
    );
    expect(filters.lead_status).not.toEqual(
      expect.arrayContaining(['inactive', 'Inactive']),
    );
  });

  it('maps leads_awarded_deals to won group filter values from app config', () => {
    const filters = buildGridFilters(
      'leads',
      { filter: 'lead_status', subFilter: 'leads_awarded_deals', search: '', sortField: 'updated_at', sortDirection: 'desc', leadtemp: '' },
      leadMenus,
      appConfig,
    );

    expect(filters.lead_status).toEqual(
      expect.arrayContaining(['awarded_franchise', 'Award Franchise']),
    );
  });

  it('maps store_status subFilter slug to stored value', () => {
    const filters = buildGridFilters(
      'stores',
      {
        filter: 'store_status',
        subFilter: 'in_development',
        search: '',
        sortField: 'updated_at',
        sortDirection: 'desc',
        leadtemp: '',
      },
      {
        menuItems: { store_status: { label: 'Unit statuses', slug: 'store_status' } },
        subMenuItems: {
          store_status: {
            in_development: { label: 'In Development', slug: 'in_development' },
          },
        },
      },
    );

    expect(filters.store_status).toBe('in_development');
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
