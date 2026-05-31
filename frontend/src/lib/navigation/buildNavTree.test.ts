import { describe, expect, it } from 'vitest';
import { buildNavigationTree } from './buildNavTree';
import type { NavItem } from '@/types/auth';

const baseMenus = {
  leads: {
    menuItems: {
      lead_status: { label: 'Lead status', slug: 'lead_status' },
      lead_owner: { label: 'Lead owner', slug: 'lead_owner' },
    },
    subMenuItems: {
      lead_status: {
        leads_active: { label: 'Active leads', slug: 'leads_active' },
        new_lead: { label: 'New Lead', slug: 'new_lead' },
      },
      lead_owner: {
        jane_doe: { label: 'Jane Doe', slug: 'jane_doe' },
      },
    },
  },
  stores: {
    menuItems: {
      store_status: { label: 'Unit statuses', slug: 'store_status' },
      store_area: { label: 'Areas', slug: 'store_area' },
    },
    subMenuItems: {
      store_status: {
        open: { label: 'open', slug: 'open' },
        pending: { label: 'pending', slug: 'pending' },
      },
      store_area: {
        north: { label: 'North', slug: 'north' },
        south: { label: 'South', slug: 'south' },
      },
    },
  },
};

const appConfig = {
  options: { brandName: 'FIL' },
  menus: {
    top_menus: [],
    menus_with_columns: baseMenus,
  },
} as const;

describe('buildNavigationTree', () => {
  it('flattens lead status filters directly under Leads', () => {
    const navigation: NavItem[] = [{ id: 'leads', label: 'Leads', path: '/reports/leads' }];

    const tree = buildNavigationTree(navigation, appConfig, () => false);
    const leads = tree[0] as NavItem;
    const labels = (leads.children ?? []).map((child) => child.label);

    expect(labels).toEqual(['Lead status', 'Active leads', 'New Lead', 'Lead owner']);
    expect(leads.children?.find((child) => child.id === 'leads-lead_owner')?.children).toHaveLength(1);
  });

  it('nests unit statuses and areas under My Units', () => {
    const navigation: NavItem[] = [{ id: 'stores', label: 'My Units', path: '/reports/stores' }];

    const tree = buildNavigationTree(navigation, appConfig, () => false);
    const stores = tree[0] as NavItem;
    const labels = (stores.children ?? []).map((child) => child.label);

    expect(labels).toEqual(['Unit statuses', 'Areas']);
    expect(stores.children?.find((child) => child.id === 'stores-store_status')?.children?.map((c) => c.label)).toEqual([
      'open',
      'pending',
    ]);
    expect(stores.children?.find((child) => child.id === 'stores-store_area')?.children?.map((c) => c.label)).toEqual([
      'North',
      'South',
    ]);
  });

  it('unwraps a lone settings parent under an admin section', () => {
    const navigation = [
      { type: 'section', label: 'Admin' },
      {
        id: 'settings',
        label: 'Settings',
        path: '/settings',
        children: [
          { id: 'profile', label: 'My profile', path: '/profile' },
          { id: 'settings-widget', label: 'Widget form', path: '/settings/widget' },
        ],
      },
    ] as const;

    const tree = buildNavigationTree(navigation as unknown as NavItem[], null, () => false);

    expect(tree).toHaveLength(3);
    expect(tree[0]).toMatchObject({ type: 'section', label: 'Admin' });
    expect(tree[1]).toMatchObject({ id: 'profile', label: 'My profile' });
    expect(tree[2]).toMatchObject({ id: 'settings-widget', label: 'Widget form' });
  });

  it('flattens the only nested filter group under a report', () => {
    const menusOnlyStatus = {
      options: { brandName: 'FIL' },
      menus: {
        top_menus: [],
        menus_with_columns: {
          stores: {
            menuItems: { store_status: { label: 'Unit statuses', slug: 'store_status' } },
            subMenuItems: {
              store_status: {
                open: { label: 'open', slug: 'open' },
                pending: { label: 'pending', slug: 'pending' },
              },
            },
          },
        },
      },
    } as const;

    const navigation: NavItem[] = [{ id: 'stores', label: 'My Units', path: '/reports/stores' }];
    const tree = buildNavigationTree(navigation, menusOnlyStatus, () => false);
    const stores = tree[0] as NavItem;

    expect((stores.children ?? []).map((child) => child.label)).toEqual(['open', 'pending']);
  });
});
