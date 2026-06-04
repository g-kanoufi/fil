import type { AppConfig } from '@/types/auth';
import {
  filterValuesForChoiceSlug,
  filterValuesForStatusGroup,
  leadApplicationStatusConfig,
} from '@/lib/leadApplicationStatus';
import type { GridQueryParams } from './useGridQueryParams';

export interface EntityMenus {
  menuItems: Record<string, { label: string | null; slug: string }>;
  subMenuItems: Record<string, Record<string, { label: string | null; slug: string }>>;
}

export const LEADS_SPECIFIC_FILTERS = ['leads_active', 'leads_awarded_deals'] as const;

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

export function buildGridFilters(
  resource: 'leads' | 'stores' | 'contacts',
  params: GridQueryParams,
  menus?: EntityMenus,
  appConfig?: AppConfig | null,
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
        const key = resolveSubFilterKey(menus, filter, subFilter);
        filters.store_status = key ?? subFilter;
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

  const statusConfig = leadApplicationStatusConfig(appConfig);
  const metaField =
    filter === 'lead_status'
      ? 'lead_status'
      : filter === 'lead_temp'
        ? 'lead_temp'
        : filter === 'lead_source'
          ? 'lead_source'
          : filter;

  if (subFilter) {
    const normalizedSub = normalizeKey(subFilter);

    if (normalizedSub === 'leads_awarded_deals') {
      const won = filterValuesForStatusGroup(appConfig, 'won');
      filters.lead_status = won.length > 0 ? [...new Set(won)] : subFilterValues(menus, filter);

      return filters;
    }

    if (normalizedSub === 'leads_active') {
      const active = filterValuesForStatusGroup(appConfig, 'active');
      const closed = filterValuesForStatusGroup(appConfig, 'closed');

      if (active.length > 0) {
        filters.lead_status = [...new Set(active)];
      } else {
        const fromMenus = collectLeadStatusFilterValues(menus, () => true);
        filters.lead_status = fromMenus.length > 0 ? fromMenus : ['active', 'new_lead', 'New Lead'];
      }

      if (closed.length > 0 && Array.isArray(filters.lead_status)) {
        const closedSet = new Set(closed);
        filters.lead_status = (filters.lead_status as string[]).filter((v) => !closedSet.has(v));
      }

      return filters;
    }

    const catalogValues = filterValuesForChoiceSlug(appConfig, normalizedSub);
    if (catalogValues.length > 0) {
      filters[metaField] = catalogValues;
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
    if (filter === 'lead_status' && statusConfig) {
      filters[metaField] = statusConfig.choices.flatMap((choice) => choice.filter_values);
    } else {
      filters[metaField] = subFilterValues(menus, filter);
    }

    return filters;
  }

  const menuItem = menus?.menuItems?.[filter];
  if (menuItem?.label) {
    filters[metaField] = [menuItem.label, menuItem.label.toLowerCase(), menuItem.slug];
  }

  return filters;
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
