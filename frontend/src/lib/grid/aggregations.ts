import type { GridBucket, GridQueryResponse } from '@/lib/api/grid';
import type { EntityMenus } from './filters';

export function getFilterCount(
  aggregations: GridQueryResponse['aggregations'] | undefined,
  aggKey: string,
  bucketKey?: string,
): number {
  const buckets = aggregations?.[aggKey]?.buckets ?? [];

  if (!bucketKey) {
    return buckets.reduce((sum, bucket) => sum + bucket.doc_count, 0);
  }

  const normalized = bucketKey.replace(/[-\s]+/g, '_').toLowerCase();
  const match = buckets.find((bucket) => {
    const key = String(bucket.key ?? '');
    return (
      key === bucketKey
      || key.replace(/[-\s]+/g, '_').toLowerCase() === normalized
    );
  });

  return match?.doc_count ?? 0;
}

export function mergeLeadOwnerSubmenu(
  menus: EntityMenus,
  aggregations: GridQueryResponse['aggregations'] | undefined,
): EntityMenus {
  const buckets = aggregations?.['meta.lead_owner']?.buckets ?? [];
  if (buckets.length === 0) {
    return menus;
  }

  const ownerSub: Record<string, { label: string | null; slug: string }> = {};

  for (const bucket of buckets) {
    const label = String(bucket.key ?? 'Not defined');
    const slug = label.replace(/[-\s]+/g, '_');
    ownerSub[slug] = { label, slug };
  }

  return {
    ...menus,
    subMenuItems: {
      ...menus.subMenuItems,
      lead_owner: ownerSub,
    },
  };
}

export function mergeLeadStatusSubmenu(
  menus: EntityMenus,
  aggregations: GridQueryResponse['aggregations'] | undefined,
): EntityMenus {
  const buckets = aggregations?.['meta.lead_status']?.buckets ?? [];
  const statusSub: Record<string, { label: string | null; slug: string }> = {
    ...(menus.subMenuItems?.lead_status ?? {}),
  };

  if (buckets.length === 0) {
    return menus;
  }

  for (const bucket of buckets) {
    const label = String(bucket.key ?? '');
    if (!label) {
      continue;
    }

    const slug = label.replace(/[-\s]+/g, '_').toLowerCase();
    if (!statusSub[slug]) {
      statusSub[slug] = { label, slug: slug || label };
    }
  }

  return {
    ...menus,
    subMenuItems: {
      ...menus.subMenuItems,
      lead_status: statusSub,
    },
  };
}

export function mergeStoreStatusSubmenu(
  menus: EntityMenus,
  aggregations: GridQueryResponse['aggregations'] | undefined,
): EntityMenus {
  const buckets = aggregations?.['meta.stores']?.buckets ?? [];
  if (buckets.length === 0) {
    return menus;
  }

  const statusSub: Record<string, { label: string | null; slug: string }> = {
    ...(menus.subMenuItems?.store_status ?? {}),
  };

  for (const bucket of buckets) {
    const label = String(bucket.key ?? '');
    if (!label) {
      continue;
    }

    const slug = label.replace(/[\s/]+/g, '_').toLowerCase();
    statusSub[slug] = { label, slug };
  }

  return {
    ...menus,
    subMenuItems: {
      ...menus.subMenuItems,
      store_status: statusSub,
    },
  };
}

export function mergeStoreAreaSubmenu(
  menus: EntityMenus,
  aggregations: GridQueryResponse['aggregations'] | undefined,
): EntityMenus {
  const buckets = aggregations?.['meta.areas']?.buckets ?? [];
  if (buckets.length === 0) {
    return menus;
  }

  const areaSub: Record<string, { label: string | null; slug: string }> = {
    ...(menus.subMenuItems?.store_area ?? {}),
  };

  for (const bucket of buckets) {
    const slug = String(bucket.key ?? '');
    const label = String((bucket as { label?: string }).label ?? bucket.key ?? '');
    if (!slug) {
      continue;
    }

    areaSub[slug] = { label: label || slug, slug };
  }

  return {
    ...menus,
    subMenuItems: {
      ...menus.subMenuItems,
      store_area: areaSub,
    },
  };
}

export function mergeContactRoleSubmenu(
  menus: EntityMenus,
  aggregations: GridQueryResponse['aggregations'] | undefined,
): EntityMenus {
  const buckets = aggregations?.['meta.contacts']?.buckets ?? [];
  if (buckets.length === 0) {
    return menus;
  }

  const roleSub: Record<string, { label: string | null; slug: string }> = {
    ...(menus.subMenuItems?.contact_role ?? {}),
  };

  for (const bucket of buckets) {
    const roleName = String(bucket.key ?? '');
    if (!roleName) {
      continue;
    }

    const slug = roleName.replace(/[-\s]+/g, '_').toLowerCase();
    const label = roleName
      .split(/[_\s-]+/)
      .filter(Boolean)
      .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
      .join(' ');

    roleSub[slug] = { label, slug };
  }

  return {
    ...menus,
    subMenuItems: {
      ...menus.subMenuItems,
      contact_role: roleSub,
    },
  };
}

export function enrichMenusWithAggregations(
  resource: 'leads' | 'stores' | 'contacts',
  menus: EntityMenus,
  aggregations: GridQueryResponse['aggregations'] | undefined,
): EntityMenus {
  if (resource === 'leads') {
    return mergeLeadOwnerSubmenu(mergeLeadStatusSubmenu(menus, aggregations), aggregations);
  }

  if (resource === 'stores') {
    return mergeStoreAreaSubmenu(mergeStoreStatusSubmenu(menus, aggregations), aggregations);
  }

  if (resource === 'contacts') {
    return mergeContactRoleSubmenu(menus, aggregations);
  }

  return menus;
}

export function tempBuckets(
  aggregations: GridQueryResponse['aggregations'] | undefined,
): GridBucket[] {
  return aggregations?.['meta.lead_temp']?.buckets ?? [];
}
