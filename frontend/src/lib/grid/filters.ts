import type { GridQueryParams } from './useGridQueryParams';

export interface EntityMenus {
  menuItems: Record<string, { label: string | null; slug: string }>;
  subMenuItems: Record<string, Record<string, { label: string | null; slug: string }>>;
}

export const LEADS_SPECIFIC_FILTERS = ['leads_active', 'leads_awarded_deals'] as const;

const INACTIVE_LEAD_STATUSES = [
  'inactive',
  'transfer sale',
  'international leads',
  'not qualified',
  'dead deal',
  'close application',
  'deny application',
];

const AWARDED_STATUSES = [
  'award portfolio deal (agreement signed for portfolio brand)',
  'award franchise (agreement signed)',
  'award area (master agreement signed)',
  'award portfolio',
  'award franchise',
  'award area',
  'award-portfolio',
  'award-franchise',
  'award-area',
];

/** Fallback when dynamic status menus are not yet hydrated (fl-react parity). */
const DEFAULT_ACTIVE_FDD_STATUSES = [
  'active',
  'disclosed',
  'waiting_period',
  'new lead',
  'New Lead',
  'viewed_intro',
  'viewed_fdd_page',
  'in_waiting_period',
  'out_of_waiting_period',
];

function normalizeKey(value: string): string {
  return value.replace(/[-\s]+/g, '_').toLowerCase();
}

function subFilterValues(
  menus: EntityMenus | undefined,
  filter: string,
): string[] {
  const subItems = menus?.subMenuItems?.[filter];
  if (!subItems) {
    return [];
  }

  return Object.values(subItems).flatMap((item) => {
    const label = item.label ?? '';
    return [label, label.toLowerCase(), item.slug, normalizeKey(item.slug)];
  }).filter(Boolean);
}

function resolveSubFilterKey(
  menus: EntityMenus | undefined,
  filter: string,
  subFilter: string,
): string | null {
  const subItems = menus?.subMenuItems?.[filter];
  if (!subItems) {
    return null;
  }

  const exact = subFilter.replaceAll('_-_', '_');
  if (subItems[exact]) {
    return exact;
  }

  const normalized = normalizeKey(subFilter);
  const found = Object.keys(subItems).find((key) => normalizeKey(key) === normalized);

  return found ?? null;
}

function isInactiveLeadStatus(key: string, label: string): boolean {
  const normalizedLabel = label.toLowerCase();

  return INACTIVE_LEAD_STATUSES.some(
    (inactive) =>
      normalizedLabel.includes(inactive) ||
      key.includes(inactive.replace(/\s/g, '_')),
  );
}

function collectLeadStatusFilterValues(
  menus: EntityMenus | undefined,
  includeEntry: (key: string, label: string) => boolean,
): string[] {
  const subItems = menus?.subMenuItems?.lead_status ?? {};
  const values: string[] = [];

  for (const [key, item] of Object.entries(subItems)) {
    const normKey = normalizeKey(key);

    if (LEADS_SPECIFIC_FILTERS.includes(normKey as (typeof LEADS_SPECIFIC_FILTERS)[number])) {
      continue;
    }

    const label = item.label ?? '';
    if (!includeEntry(normKey, label)) {
      continue;
    }

    values.push(label, label.toLowerCase(), item.slug, normalizeKey(item.slug));
  }

  return values.filter(Boolean);
}

export function buildGridFilters(
  resource: 'leads' | 'stores' | 'contacts',
  params: GridQueryParams,
  menus?: EntityMenus,
): Record<string, unknown> {
  const filters: Record<string, unknown> = {};
  const { filter, subFilter, leadtemp } = params;

  if (leadtemp && resource === 'leads') {
    filters.lead_temp = [leadtemp, leadtemp.charAt(0).toUpperCase() + leadtemp.slice(1)];
  }

  if (!filter) {
    return filters;
  }

  if (resource === 'contacts') {
    if (filter === 'contact_role' && subFilter) {
      filters.role = normalizeKey(subFilter);
    }

    return filters;
  }

  if (resource === 'stores') {
    if (filter === 'store_status') {
      if (subFilter) {
        filters.store_status = subFilter.replace(/_/g, ' ');
      } else {
        filters.store_status = subFilterValues(menus, filter);
      }
    } else if (filter === 'store_area' && subFilter) {
      filters.area_id = subFilter;
    }

    return filters;
  }

  // leads
  if (filter === 'lead_owner') {
    if (subFilter) {
      const ownerName = subFilter
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
      filters.lead_owner = ownerName;
    }

    return filters;
  }

  const metaField =
    filter === 'lead_status'
      ? 'lead_fdd_status'
      : filter === 'lead_temp'
        ? 'lead_temp'
        : filter === 'lead_source'
          ? 'lead_source'
          : filter;

  if (subFilter) {
    const normalizedSub = normalizeKey(subFilter);

    if (normalizedSub === 'leads_awarded_deals') {
      filters.lead_fdd_status = AWARDED_STATUSES;
      return filters;
    }

    if (normalizedSub === 'leads_active') {
      const active = collectLeadStatusFilterValues(
        menus,
        (key, label) => !isInactiveLeadStatus(key, label),
      );

      filters.lead_fdd_status =
        active.length > 0 ? [...new Set(active)] : DEFAULT_ACTIVE_FDD_STATUSES;

      return filters;
    }

    const resolvedKey = resolveSubFilterKey(menus, filter, subFilter);
    const subItem = resolvedKey ? menus?.subMenuItems?.[filter]?.[resolvedKey] : null;
    const values = subItem
      ? [subItem.label, subItem.label?.toLowerCase(), subItem.slug, normalizeKey(subItem.slug)].filter(Boolean)
      : [subFilter.replace(/_/g, ' '), subFilter, normalizedSub];

    filters[metaField] = values;
    return filters;
  }

  if (menus?.subMenuItems?.[filter]) {
    filters[metaField] = subFilterValues(menus, filter);
    return filters;
  }

  const menuItem = menus?.menuItems?.[filter];
  if (menuItem?.label) {
    filters[metaField] = [menuItem.label, menuItem.label.toLowerCase(), menuItem.slug];
  }

  return filters;
}
