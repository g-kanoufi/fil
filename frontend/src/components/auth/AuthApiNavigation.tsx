import { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import type { ApiError } from '@/lib/api/client';
import { resolveApiAuthFailure, setApiAuthErrorHandler } from '@/lib/api/authBridge';

interface AuthApiNavigationProps {
  onSessionExpired: () => void;
}

/**
 * Registers global API auth failure routing. Must render inside React Router.
 */
export function AuthApiNavigation({ onSessionExpired }: AuthApiNavigationProps) {
  const navigate = useNavigate();

  useEffect(() => {
    setApiAuthErrorHandler((error: ApiError) => {
      const failure = resolveApiAuthFailure(error);

      if (failure === 'login') {
        onSessionExpired();
        navigate('/login', { replace: true });

        return 'login';
      }

      if (failure === 'forbidden') {
        navigate('/forbidden', { replace: true });

        return 'forbidden';
      }

      return undefined;
    });

    return () => setApiAuthErrorHandler(null);
  }, [navigate, onSessionExpired]);

  return null;
}
