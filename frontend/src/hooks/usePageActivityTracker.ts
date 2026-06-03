import { useEffect, useRef } from 'react';
import { useLocation } from 'react-router-dom';
import { postActivityPageViews } from '@/lib/api/activity';

const DEBOUNCE_MS = 10_000;
const CLIENT_THROTTLE_MS = 30_000;
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

function shouldTrack(pathname: string): boolean {
  if (SKIP_PREFIXES.some((prefix) => pathname.startsWith(prefix))) {
    return false;
  }

  const path = pathname.length > 1 ? pathname.replace(/\/$/, '') : pathname;

  if (GRID_PATHS.has(path)) {
    return false;
  }

  return true;
}

export function usePageActivityTracker(): void {
  const location = useLocation();
  const pendingRef = useRef<string | null>(null);
  const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const lastFlushRef = useRef(0);

  const flush = (path: string, force = false) => {
    if (!shouldTrack(path)) {
      return;
    }

    const lastRecorded = sessionStorage.getItem(SESSION_KEY);

    if (!force && lastRecorded === path) {
      return;
    }

    const now = Date.now();

    if (!force && now - lastFlushRef.current < CLIENT_THROTTLE_MS) {
      return;
    }

    lastFlushRef.current = now;
    sessionStorage.setItem(SESSION_KEY, path);

    void postActivityPageViews([path]).catch(() => {
      sessionStorage.removeItem(SESSION_KEY);
    });
  };

  useEffect(() => {
    const path = location.pathname;

    if (!shouldTrack(path)) {
      return;
    }

    pendingRef.current = path;

    if (debounceRef.current !== null) {
      clearTimeout(debounceRef.current);
    }

    debounceRef.current = setTimeout(() => {
      if (pendingRef.current !== null) {
        flush(pendingRef.current);
      }
    }, DEBOUNCE_MS);

    return () => {
      if (debounceRef.current !== null) {
        clearTimeout(debounceRef.current);
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
