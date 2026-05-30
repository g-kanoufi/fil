import { IconMenu } from '@/components/icons/NavIcons';
import { UserMenu } from '@/components/layout/UserMenu';
import { iconButtonClasses } from '@/lib/ui/tokens';
import { cn } from '@/lib/cn';
import { useAuth } from '@/providers/AuthProvider';

interface HeaderProps {
  onMenuClick: () => void;
}

export function Header({ onMenuClick }: HeaderProps) {
  const { user } = useAuth();

  return (
    <header className="relative sticky top-0 z-30 flex h-[var(--header-height)] items-center justify-between border-b border-border bg-surface px-4 after:pointer-events-none after:absolute after:inset-x-0 after:bottom-0 after:h-0.5 after:bg-primary/15 lg:px-6">
      <div className="flex items-center gap-3">
        <button
          type="button"
          aria-label="Open menu"
          className={cn(iconButtonClasses, 'lg:hidden')}
          onClick={onMenuClick}
        >
          <IconMenu className="h-5 w-5" />
        </button>
        <div>
          <p className="text-sm text-muted">Welcome back</p>
          <p className="font-medium text-foreground">{user?.name ?? 'Staff'}</p>
        </div>
      </div>

      <UserMenu />
    </header>
  );
}
