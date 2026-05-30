import { type ReactNode } from 'react';
import { Navigate } from 'react-router-dom';
import { useAuth } from '@/providers/AuthProvider';

interface RequirePermissionProps {
  permission: string;
  children: ReactNode;
  fallback?: string;
}

export function RequirePermission({
  permission,
  children,
  fallback = '/',
}: RequirePermissionProps) {
  const { status, can } = useAuth();

  if (status === 'loading') {
    return <p data-testid="auth-loading">Loading…</p>;
  }

  if (!can(permission)) {
    return <Navigate to="/forbidden" replace />;
  }

  return children;
}
