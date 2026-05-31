import { useMemo } from 'react';
import { BrandMark } from '@/components/branding/BrandMark';
import { NavMenu } from '@/components/layout/NavMenu';
import { cn } from '@/lib/cn';
import { buildNavigationTree } from '@/lib/navigation/buildNavTree';
import { useAuth } from '@/providers/AuthProvider';

interface SidebarProps {
  open: boolean;
  onClose: () => void;
}

export function Sidebar({ open, onClose }: SidebarProps) {
  const { user, appConfig, isUiDisabled } = useAuth();

  const navigation = useMemo(
    () =>
      user?.navigation
        ? buildNavigationTree(user.navigation, appConfig, isUiDisabled)
        : [],
    [user?.navigation, appConfig, isUiDisabled],
  );

  return (
    <>
      {open ? (
        <button
          type="button"
          aria-label="Close menu"
          className="fixed inset-0 z-40 bg-black/40 lg:hidden"
          onClick={onClose}
        />
      ) : null}

      <aside
        className={cn(
          'fixed inset-y-0 left-0 z-50 flex w-[var(--sidebar-width)] flex-col bg-sidebar transition-transform',
          open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
        )}
      >
        <div className="flex h-[var(--header-height)] items-center border-b border-white/10 px-5">
          <BrandMark variant="sidebar" />
        </div>

        <nav aria-label="Staff navigation" className="flex-1 overflow-y-auto px-2 py-3">
          <NavMenu items={navigation} onClose={onClose} />
        </nav>
      </aside>
    </>
  );
}
