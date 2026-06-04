import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from 'react';
import {
  fetchPortalSession,
  loginPortal,
  logoutPortal,
  setupPortalPassword,
  type PortalSessionUser,
} from '@/lib/api/portal';

type PortalAuthStatus = 'loading' | 'authenticated' | 'unauthenticated';

interface PortalAuthContextValue {
  status: PortalAuthStatus;
  user: PortalSessionUser | null;
  login: (email: string, password: string) => Promise<void>;
  setupPassword: (token: string, password: string, confirmation: string) => Promise<void>;
  logout: () => Promise<void>;
  refresh: () => Promise<void>;
}

const PortalAuthContext = createContext<PortalAuthContextValue | null>(null);

export function PortalAuthProvider({ children }: { children: ReactNode }) {
  const [status, setStatus] = useState<PortalAuthStatus>('loading');
  const [user, setUser] = useState<PortalSessionUser | null>(null);

  const clearSession = useCallback(() => {
    setUser(null);
    setStatus('unauthenticated');
  }, []);

  const refresh = useCallback(async () => {
    try {
      setUser(await fetchPortalSession());
      setStatus('authenticated');
    } catch {
      clearSession();
    }
  }, [clearSession]);

  useEffect(() => {
    void refresh();
  }, [refresh]);

  const login = useCallback(async (email: string, password: string) => {
    setUser(await loginPortal(email, password));
    setStatus('authenticated');
  }, []);

  const setupPassword = useCallback(async (token: string, password: string, confirmation: string) => {
    setUser(await setupPortalPassword(token, password, confirmation));
    setStatus('authenticated');
  }, []);

  const logoutHandler = useCallback(async () => {
    try {
      await logoutPortal();
    } finally {
      clearSession();
    }
  }, [clearSession]);

  const value = useMemo(
    () => ({
      status,
      user,
      login,
      setupPassword,
      logout: logoutHandler,
      refresh,
    }),
    [status, user, login, setupPassword, logoutHandler, refresh],
  );

  return <PortalAuthContext.Provider value={value}>{children}</PortalAuthContext.Provider>;
}

export function usePortalAuth(): PortalAuthContextValue {
  const context = useContext(PortalAuthContext);

  if (context === null) {
    throw new Error('usePortalAuth must be used within PortalAuthProvider');
  }

  return context;
}
