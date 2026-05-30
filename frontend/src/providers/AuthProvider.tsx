import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from 'react';
import { fetchSession, login as loginRequest, logout as logoutRequest } from '@/lib/api/session';
import { fetchAppConfig } from '@/lib/api/app-config';
import type { AppConfig, AuthStatus, SessionUser } from '@/types/auth';

interface AuthContextValue {
  status: AuthStatus;
  user: SessionUser | null;
  appConfig: AppConfig | null;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  expireSession: () => void;
  refresh: () => Promise<void>;
  can: (permission: string) => boolean;
  hasRole: (role: string) => boolean;
  isUiDisabled: (domain: keyof SessionUser['ui_restrictions'], key: string) => boolean;
  isFieldHidden: (key: string) => boolean;
  isFieldReadonly: (key: string) => boolean;
  canSeeNotes: (entity: string, kind?: 'notes' | 'private_notes') => boolean;
}

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [status, setStatus] = useState<AuthStatus>('loading');
  const [user, setUser] = useState<SessionUser | null>(null);
  const [appConfig, setAppConfig] = useState<AppConfig | null>(null);

  const clearSession = useCallback(() => {
    setUser(null);
    setAppConfig(null);
    setStatus('unauthenticated');
  }, []);

  const refresh = useCallback(async () => {
    try {
      const session = await fetchSession();
      setUser(session);
      setAppConfig(await fetchAppConfig());
      setStatus('authenticated');
    } catch {
      clearSession();
    }
  }, [clearSession]);

  useEffect(() => {
    void refresh();
  }, [refresh]);

  const login = useCallback(async (email: string, password: string) => {
    const session = await loginRequest(email, password);
    setUser(session);
    setAppConfig(await fetchAppConfig());
    setStatus('authenticated');
  }, []);

  const logout = useCallback(async () => {
    try {
      await logoutRequest();
    } finally {
      clearSession();
    }
  }, [clearSession]);

  const can = useCallback(
    (permission: string) => user?.permissions.includes(permission) ?? false,
    [user],
  );

  const hasRole = useCallback(
    (role: string) => user?.roles.includes(role) ?? false,
    [user],
  );

  const isUiDisabled = useCallback(
    (domain: keyof SessionUser['ui_restrictions'], key: string) => {
      if (!user) {
        return true;
      }

      const block = user.ui_restrictions[domain];

      if (Array.isArray(block)) {
        return block.includes(key);
      }

      if (block && typeof block === 'object' && 'menuItems' in block) {
        const shaped = block as { menuItems: string[]; subMenuItems: string[] };
        return shaped.menuItems.includes(key) || shaped.subMenuItems.includes(key);
      }

      return false;
    },
    [user],
  );

  const isFieldHidden = useCallback(
    (key: string) => user?.hidden_field_keys.includes(key) ?? true,
    [user],
  );

  const isFieldReadonly = useCallback(
    (key: string) => user?.readonly_field_keys.includes(key) ?? false,
    [user],
  );

  const canSeeNotes = useCallback(
    (entity: string, kind: 'notes' | 'private_notes' = 'notes') => {
      const grants = user?.authorized_notes[entity];

      return grants?.[kind] ?? false;
    },
    [user],
  );

  const value = useMemo(
    () => ({
      status,
      user,
      appConfig,
      login,
      logout,
      expireSession: clearSession,
      refresh,
      can,
      hasRole,
      isUiDisabled,
      isFieldHidden,
      isFieldReadonly,
      canSeeNotes,
    }),
    [
      status,
      user,
      appConfig,
      login,
      logout,
      clearSession,
      refresh,
      can,
      hasRole,
      isUiDisabled,
      isFieldHidden,
      isFieldReadonly,
      canSeeNotes,
    ],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext);

  if (!context) {
    throw new Error('useAuth must be used within AuthProvider');
  }

  return context;
}

export function useCan(permission: string): boolean {
  const { can } = useAuth();

  return can(permission);
}
