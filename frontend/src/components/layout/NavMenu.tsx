import { useMemo, useState } from 'react';
import { NavLink, useLocation } from 'react-router-dom';
import { IconChevronDown, IconChevronRight, NavIcon } from '@/components/icons/NavIcons';
import { cn } from '@/lib/cn';
import { focusRing } from '@/lib/ui/tokens';
import {
  navItemHasActiveDescendant,
  navItemMatches,
} from '@/lib/navigation/navMatch';
import type { NavItem, NavSection } from '@/types/auth';

interface NavMenuItemProps {
  item: NavItem;
  depth?: number;
  onClose?: () => void;
  expandedKeys: string[];
  toggleExpanded: (key: string) => void;
  locationPath: string;
  locationSearch: string;
}

function itemIsExpandedAncestor(
  locationPath: string,
  locationSearch: string,
  item: NavItem,
): boolean {
  if (navItemMatches(locationPath, locationSearch, item.path)) {
    return true;
  }

  return navItemHasActiveDescendant(locationPath, locationSearch, item.children ?? []);
}

function navRowClass({
  depth,
  directlyActive,
  hasActiveChild,
}: {
  depth: number;
  directlyActive: boolean;
  hasActiveChild: boolean;
}) {
  return cn(
    'flex w-full min-w-0 items-center gap-2.5 rounded-md text-left transition-colors',
    depth === 0 ? 'px-3 py-2.5 text-sm font-medium' : 'py-2 pr-3 text-[13px] font-normal',
    directlyActive &&
      (depth === 0
        ? 'bg-[var(--color-sidebar-active)] text-white shadow-[inset_3px_0_0_0_rgba(255,255,255,0.85)]'
        : 'bg-[var(--color-sidebar-active)] font-medium text-white shadow-[inset_3px_0_0_0_rgba(96,165,250,0.95)]'),
    !directlyActive &&
      hasActiveChild &&
      (depth === 0
        ? 'bg-[var(--color-sidebar-hover)] text-white'
        : 'text-white/85'),
    !directlyActive &&
      !hasActiveChild &&
      'text-white/70 hover:bg-[var(--color-sidebar-hover)] hover:text-white',
  );
}

function NavMenuItem({
  item,
  depth = 0,
  onClose,
  expandedKeys,
  toggleExpanded,
  locationPath,
  locationSearch,
}: NavMenuItemProps) {
  const hasChildren = (item.children?.length ?? 0) > 0;
  const isExpanded = expandedKeys.includes(item.id);
  const directlyActive = navItemMatches(locationPath, locationSearch, item.path);
  const hasActiveChild =
    hasChildren &&
    !directlyActive &&
    navItemHasActiveDescendant(locationPath, locationSearch, item.children ?? []);
  const paddingLeft = depth === 0 ? undefined : `${0.75 + depth * 0.625}rem`;

  if (hasChildren) {
    return (
      <li className="w-full">
        <div
          className={cn(
            'flex w-full min-w-0 items-stretch',
            navRowClass({ depth, directlyActive, hasActiveChild }),
          )}
        >
          <NavLink
            to={item.path}
            onClick={onClose}
            style={{ paddingLeft }}
            end={!item.path.includes('?')}
            className="flex min-w-0 flex-1 items-center gap-2.5 outline-none"
          >
            {depth === 0 ? <NavIcon id={item.id} /> : null}
            <span className="truncate">{item.label}</span>
          </NavLink>
          <button
            type="button"
            aria-expanded={isExpanded}
            aria-label={`${isExpanded ? 'Collapse' : 'Expand'} ${item.label}`}
            className={cn(
              'mr-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-white/55 transition-colors hover:bg-white/10 hover:text-white',
              focusRing,
            )}
            onClick={(event) => {
              event.preventDefault();
              toggleExpanded(item.id);
            }}
          >
            {isExpanded ? <IconChevronDown className="h-4 w-4" /> : <IconChevronRight className="h-4 w-4" />}
          </button>
        </div>
        {isExpanded ? (
          <ul className="mt-0.5 space-y-0.5 border-l border-white/10 py-0.5 pl-2" style={{ marginLeft: depth === 0 ? '0.75rem' : '1.25rem' }}>
            {(item.children ?? []).map((child) => (
              <NavMenuItem
                key={child.id}
                item={child}
                depth={depth + 1}
                onClose={onClose}
                expandedKeys={expandedKeys}
                toggleExpanded={toggleExpanded}
                locationPath={locationPath}
                locationSearch={locationSearch}
              />
            ))}
          </ul>
        ) : null}
      </li>
    );
  }

  return (
    <li className="w-full">
      <NavLink
        to={item.path}
        onClick={onClose}
        isActive={() => navItemMatches(locationPath, locationSearch, item.path)}
        style={{ paddingLeft }}
        className={() =>
          navRowClass({
            depth,
            directlyActive: navItemMatches(locationPath, locationSearch, item.path),
            hasActiveChild: false,
          })
        }
      >
        {depth === 0 ? <NavIcon id={item.id} /> : null}
        <span className="truncate">{item.label}</span>
      </NavLink>
    </li>
  );
}

interface NavMenuProps {
  items: Array<NavItem | NavSection>;
  onClose?: () => void;
}

export function NavMenu({ items, onClose }: NavMenuProps) {
  const location = useLocation();
  const locationPath = location.pathname || '/';
  const locationSearch = location.search;

  const autoExpanded = useMemo(() => {
    const keys: string[] = [];

    const walk = (entries: Array<NavItem | NavSection>) => {
      for (const entry of entries) {
        if ('type' in entry && entry.type === 'section') {
          continue;
        }

        const item = entry as NavItem;

        if ((item.children?.length ?? 0) > 0 && itemIsExpandedAncestor(locationPath, locationSearch, item)) {
          keys.push(item.id);
        }

        if (item.children) {
          walk(item.children);
        }
      }
    };

    walk(items);

    return keys;
  }, [items, locationPath, locationSearch]);

  const [userExpanded, setUserExpanded] = useState<string[]>([]);
  const expandedKeys = useMemo(
    () => Array.from(new Set([...autoExpanded, ...userExpanded])),
    [autoExpanded, userExpanded],
  );

  const toggleExpanded = (key: string) => {
    setUserExpanded((current) =>
      current.includes(key) ? current.filter((value) => value !== key) : [...current, key],
    );
  };

  return (
    <ul className="flex w-full flex-col gap-0.5">
      {items.map((entry) => {
        if ('type' in entry && entry.type === 'section') {
          return (
            <li key={`section-${entry.label}`} className="w-full pt-3 first:pt-0">
              <p className="px-3 pb-1.5 text-[11px] font-semibold uppercase tracking-wider text-white/40">
                {entry.label}
              </p>
            </li>
          );
        }

        const item = entry as NavItem;

        return (
          <NavMenuItem
            key={item.id}
            item={item}
            onClose={onClose}
            expandedKeys={expandedKeys}
            toggleExpanded={toggleExpanded}
            locationPath={locationPath}
            locationSearch={locationSearch}
          />
        );
      })}
    </ul>
  );
}
