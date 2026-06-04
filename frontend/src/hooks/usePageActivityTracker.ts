import { useEffect, useRef } from 'react';
import { useLocation } from 'react-router-dom';
import { postActivityPageViews } from '@/lib/api/activity';

const DEBOUNCE_MS = 3_000;
const SESSION_KEY = 'fil:last-recorded-path';

const SKIP_PREFIXES = ['/login', '/forbidden'];

const GRID_PATHS = new Set([
  '/reports/leads',
  '/reports/stores',
  '/reports/contacts',
  '/reports/closings',
  '/reports/royalties',
  '/reports/ach',
]);

/** Strip Vite/nginx /app prefix when pathname includes it. */
export function normalizeAppPath(pathname: string): string {
  if (pathname === '/app' || pathname === '/app/') {
    return '/';
  }

  if (pathname.startsWith('/app/')) {
    return pathname.slice(4) || '/';
  }

  return pathname.length > 1 ? pathname.replace(/\/$/, '') : pathname;
}

function shouldTrack(pathname: string): boolean {
  const path = normalizeAppPath(pathname);

  if (SKIP_PREFIXES.some((prefix) => path.startsWith(prefix))) {
    return false;
  }

  if (GRID_PATHS.has(path)) {
    return false;
  }

  return true;
}

export function usePageActivityTracker(): void {
  const location = useLocation();
  const pendingRef = useRef<string | null>(null);
  const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  const flush = (rawPath: string, force = false) => {
    const path = normalizeAppPath(rawPath);

    if (!shouldTrack(rawPath)) {
      return;
    }

    const lastRecorded = sessionStorage.getItem(SESSION_KEY);

    if (!force && lastRecorded === path) {
      return;
    }

    sessionStorage.setItem(SESSION_KEY, path);

    void postActivityPageViews([path]).catch(() => {
      sessionStorage.removeItem(SESSION_KEY);
    });
  };

  useEffect(() => {
    const path = location.pathname;

    if (!shouldTrack(path)) {
      pendingRef.current = null;

      return;
    }

    pendingRef.current = path;

    debounceRef.current = setTimeout(() => {
      if (pendingRef.current === path) {
        flush(path);
      }
    }, DEBOUNCE_MS);

    return () => {
      if (debounceRef.current === null) {
        return;
      }

      clearTimeout(debounceRef.current);
      debounceRef.current = null;

      const abandoned = pendingRef.current;

      if (abandoned !== null && shouldTrack(abandoned)) {
        flush(abandoned);
      }
    };
  }, [location.pathname]);

  useEffect(() => {
    const onHide = () => {
      const path = pendingRef.current ?? location.pathname;

      if (path && shouldTrack(path)) {
        flush(path, true);
      }
    };

    document.addEventListener('visibilitychange', onHide);

    return () => document.removeEventListener('visibilitychange', onHide);
  }, [location.pathname]);
}
