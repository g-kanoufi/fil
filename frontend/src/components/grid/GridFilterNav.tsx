import { cn } from '@/lib/cn';
import {
  chipActive,
  chipCountActive,
  chipCountInactive,
  chipInactive,
  focusRing,
} from '@/lib/ui/tokens';
import { getFilterCount } from '@/lib/grid/aggregations';
import type { EntityMenus } from '@/lib/grid/filters';
import type { GridQueryResponse } from '@/lib/api/grid';
import { GRID_FLAT_SUBMENU_GROUPS } from '@/lib/navigation/buildNavTree';

interface GridFilterNavProps {
  resource: 'leads' | 'stores' | 'contacts';
  menus: EntityMenus;
  aggregations?: GridQueryResponse['aggregations'];
  activeFilter: string;
  activeSubFilter: string;
  onSelect: (filter: string, subFilter?: string) => void;
  isDisabled: (key: string, isSub?: boolean) => boolean;
}

function aggKeyForFilter(resource: string, filter: string): string | null {
  if (resource === 'leads') {
    if (filter === 'lead_status') return 'meta.lead_status';
    if (filter === 'lead_owner') return 'meta.lead_owner';
    if (filter === 'lead_temp') return 'meta.lead_temp';
    if (filter === 'lead_source') return 'meta.lead_source';
  }
  if (resource === 'stores' && filter === 'store_status') return 'meta.stores';
  if (resource === 'stores' && filter === 'store_area') return 'meta.areas';
  if (resource === 'contacts' && filter === 'contact_role') return 'meta.contacts';
  return null;
}

function isFlatFilterGroup(resource: GridFilterNavProps['resource'], key: string): boolean {
  return GRID_FLAT_SUBMENU_GROUPS[resource]?.includes(key) ?? false;
}

export function GridFilterNav({
  resource,
  menus,
  aggregations,
  activeFilter,
  activeSubFilter,
  onSelect,
  isDisabled,
}: GridFilterNavProps) {
  const menuKeys = Object.keys(menus.menuItems ?? {});

  if (menuKeys.length === 0) {
    return null;
  }

  return (
    <div className="mb-4 flex flex-wrap gap-2">
      <FilterChip
        label="All"
        count={aggregations ? getFilterCount(aggregations, aggKeyForFilter(resource, menuKeys[0]) ?? '') : undefined}
        active={!activeFilter}
        onClick={() => onSelect('')}
      />

      {menuKeys.map((key) => {
        if (isDisabled(key)) {
          return null;
        }

        const item = menus.menuItems[key];
        const aggKey = aggKeyForFilter(resource, key);
        const subItems = menus.subMenuItems?.[key] ?? {};
        const subEntries = Object.entries(subItems).filter(([subKey]) => !isDisabled(subKey, true));

        if (isFlatFilterGroup(resource, key) && subEntries.length > 0) {
          return (
            <div key={key} className="flex flex-wrap gap-2">
              {subEntries.map(([subKey, subItem]) => (
                <FilterChip
                  key={subKey}
                  label={subItem.label ?? subKey}
                  count={
                    aggKey && subKey.startsWith('leads_')
                      ? undefined
                      : aggKey
                        ? getFilterCount(
                            aggregations,
                            aggKey,
                            key === 'store_area' ? subKey : (subItem.label ?? subKey),
                          )
                        : undefined
                  }
                  active={activeFilter === key && activeSubFilter === subKey}
                  onClick={() => onSelect(key, subKey)}
                />
              ))}
            </div>
          );
        }

        if (subEntries.length > 0) {
          return (
            <div key={key} className="flex flex-wrap items-center gap-1 rounded-lg border border-border bg-surface px-2 py-1">
              <span className="px-2 text-xs font-semibold uppercase tracking-wide text-muted">
                {item.label ?? key}
              </span>
              {subEntries.map(([subKey, subItem]) => (
                <FilterChip
                  key={subKey}
                  label={subItem.label ?? subKey}
                  count={
                    aggKey ? getFilterCount(aggregations, aggKey, subItem.label ?? subKey) : undefined
                  }
                  active={activeFilter === key && activeSubFilter === subKey}
                  onClick={() => onSelect(key, subKey)}
                  compact
                />
              ))}
            </div>
          );
        }

        return (
          <FilterChip
            key={key}
            label={item.label ?? key}
            count={aggKey ? getFilterCount(aggregations, aggKey) : undefined}
            active={activeFilter === key && !activeSubFilter}
            onClick={() => onSelect(key)}
          />
        );
      })}
    </div>
  );
}

function FilterChip({
  label,
  count,
  active,
  onClick,
  compact,
}: {
  label: string;
  count?: number;
  active: boolean;
  onClick: () => void;
  compact?: boolean;
}) {
  return (
    <button
      type="button"
      onClick={onClick}
      className={cn(
        'inline-flex items-center gap-2 rounded-full border text-sm transition-colors',
        compact ? 'px-2.5 py-1' : 'px-3 py-1.5',
        active ? chipActive : chipInactive,
        focusRing,
      )}
    >
      <span>{label}</span>
      {count !== undefined ? (
        <span className={cn(active ? chipCountActive : chipCountInactive)}>
          {count.toLocaleString()}
        </span>
      ) : null}
    </button>
  );
}
