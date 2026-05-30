import { useMemo, useState } from 'react';
import { NavLink, useLocation } from 'react-router-dom';
import { IconChevronDown, IconChevronRight, NavIcon } from '@/components/icons/NavIcons';
import { cn } from '@/lib/cn';
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

function navLinkClass({
  depth,
  directlyActive,
  hasActiveChild,
}: {
  depth: number;
  directlyActive: boolean;
  hasActiveChild: boolean;
}) {
  return cn(
    'flex min-w-0 items-center gap-3 rounded-md py-2 text-sm font-medium transition-colors',
    depth === 0 ? 'px-3' : 'pr-3 text-[13px]',
    directlyActive &&
      (depth === 0
        ? 'bg-white/10 text-white'
        : 'border-l-2 border-white/35 bg-white/5 pl-2.5 text-white'),
    !directlyActive &&
      hasActiveChild &&
      (depth === 0 ? 'text-white/90' : 'text-white/80'),
    !directlyActive &&
      !hasActiveChild &&
      'text-white/65 hover:bg-white/5 hover:text-white',
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
  const paddingLeft = depth === 0 ? undefined : `${0.5 + depth * 0.65}rem`;

  if (hasChildren) {
    return (
      <li>
        <div className="flex items-center">
          <NavLink
            to={item.path}
            onClick={onClose}
            style={{ paddingLeft }}
            end={!item.path.includes('?')}
            className={navLinkClass({ depth, directlyActive, hasActiveChild })}
          >
            {depth === 0 ? <NavIcon id={item.id} /> : null}
            <span className="truncate">{item.label}</span>
          </NavLink>
          <button
            type="button"
            aria-expanded={isExpanded}
            aria-label={`${isExpanded ? 'Collapse' : 'Expand'} ${item.label}`}
            className={cn(
              'rounded-md px-2 py-2 transition-colors',
              hasActiveChild ? 'text-white/75' : 'text-white/45 hover:bg-white/5 hover:text-white/80',
            )}
            onClick={() => toggleExpanded(item.id)}
          >
            {isExpanded ? <IconChevronDown className="h-4 w-4" /> : <IconChevronRight className="h-4 w-4" />}
          </button>
        </div>
        {isExpanded ? (
          <ul className="ml-4 mt-0.5 space-y-0.5 border-l border-white/10 pl-2">
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
    <li>
      <NavLink
        to={item.path}
        onClick={onClose}
        end={!item.path.includes('?')}
        style={{ paddingLeft }}
        className={({ isActive: linkActive }) =>
          navLinkClass({
            depth,
            directlyActive: linkActive || navItemMatches(locationPath, locationSearch, item.path),
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
    <ul className="space-y-0.5">
      {items.map((entry) => {
        if ('type' in entry && entry.type === 'section') {
          return (
            <li key={`section-${entry.label}`} className="pt-3">
              <p className="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wide text-white/40">
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
