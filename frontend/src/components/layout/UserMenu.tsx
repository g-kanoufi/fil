import { useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import { IconBell, IconClose, IconLogout, IconMoon, IconProfile, IconSun } from '@/components/icons/NavIcons';
import { Avatar } from '@/components/ui/Avatar';
import { Button } from '@/components/ui/Button';
import { useDialogA11y } from '@/lib/a11y/useDialogA11y';
import { useAuth } from '@/providers/AuthProvider';
import { useTheme, type ThemeMode } from '@/providers/ThemeProvider';
import { cn } from '@/lib/cn';
import { surface } from '@/lib/ui/tokens';

const themeOptions: { value: ThemeMode; label: string; icon: typeof IconSun }[] = [
  { value: 'light', label: 'Light', icon: IconSun },
  { value: 'dark', label: 'Dark', icon: IconMoon },
  { value: 'system', label: 'System', icon: IconSun },
];

export function UserMenu() {
  const { user, logout } = useAuth();
  const { mode, setMode } = useTheme();
  const [open, setOpen] = useState(false);
  const panelRef = useRef<HTMLDivElement>(null);

  useDialogA11y(open, () => setOpen(false), panelRef);

  if (!user) {
    return null;
  }

  const displayName = user.name || user.email;

  return (
    <>
      <button
        type="button"
        aria-expanded={open}
        aria-haspopup="dialog"
        aria-label="Account menu"
        className="rounded-full transition hover:ring-2 hover:ring-focus/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus"
        onClick={() => setOpen(true)}
      >
        <Avatar name={displayName} />
      </button>

      {open ? (
        <div className="fixed inset-0 z-50 flex justify-end">
          <button
            type="button"
            aria-label="Close account menu"
            className="absolute inset-0 bg-black/40 backdrop-blur-[1px] transition-opacity"
            onClick={() => setOpen(false)}
          />

          <div
            ref={panelRef}
            role="dialog"
            aria-modal="true"
            aria-label="Account"
            tabIndex={-1}
            className="relative flex h-full w-full max-w-sm flex-col border-l border-border bg-surface shadow-2xl animate-in slide-in-from-right duration-200"
          >
            <div className="flex items-center justify-between border-b border-border px-5 py-4">
              <div className="flex items-center gap-3">
                <Avatar name={displayName} />
                <div className="min-w-0">
                  <p className="truncate font-semibold text-foreground">{displayName}</p>
                  <p className="truncate text-sm text-muted">{user.email}</p>
                  {user.primary_role ? (
                    <p className="mt-0.5 text-xs uppercase tracking-wide text-muted">{user.primary_role.replace(/_/g, ' ')}</p>
                  ) : null}
                </div>
              </div>
              <button
                type="button"
                aria-label="Close"
                className="rounded-lg p-2 text-muted transition hover:bg-surface-muted hover:text-foreground"
                onClick={() => setOpen(false)}
              >
                <IconClose className="h-5 w-5" />
              </button>
            </div>

            <nav className="flex-1 overflow-y-auto px-3 py-4">
              <ul className="space-y-1">
                <li>
                  <Link
                    to="/profile"
                    className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-foreground transition hover:bg-surface-muted"
                    onClick={() => setOpen(false)}
                  >
                    <IconProfile className="h-5 w-5 text-muted" />
                    My profile
                  </Link>
                </li>
                <li>
                  <Link
                    to="/settings/notifications"
                    className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-foreground transition hover:bg-surface-muted"
                    onClick={() => setOpen(false)}
                  >
                    <IconBell className="h-5 w-5 text-muted" />
                    Notification preferences
                  </Link>
                </li>
              </ul>

              <div className="mt-6 px-3">
                <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-muted">Appearance</p>
                <div className="grid grid-cols-3 gap-2">
                  {themeOptions.map(({ value, label, icon: Icon }) => (
                    <button
                      key={value}
                      type="button"
                      className={cn(
                        'flex flex-col items-center gap-1.5 rounded-lg border px-2 py-2.5 text-xs font-medium transition',
                        mode === value
                          ? surface.themePickerActive
                          : 'border-border text-muted hover:border-border-strong hover:bg-surface-muted hover:text-foreground',
                      )}
                      onClick={() => setMode(value)}
                    >
                      <Icon className="h-4 w-4" />
                      {label}
                    </button>
                  ))}
                </div>
              </div>
            </nav>

            <div className="border-t border-border p-4">
              <Button
                variant="secondary"
                className="w-full justify-center gap-2"
                onClick={async () => {
                  setOpen(false);
                  await logout();
                }}
              >
                <IconLogout className="h-4 w-4" />
                Sign out
              </Button>
            </div>
          </div>
        </div>
      ) : null}
    </>
  );
}
