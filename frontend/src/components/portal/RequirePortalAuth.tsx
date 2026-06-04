import { Navigate, Outlet } from 'react-router-dom';
import { RouteFallback } from '@/components/layout/RouteFallback';
import { usePortalAuth } from '@/providers/PortalAuthProvider';

export function RequirePortalAuth() {
  const { status } = usePortalAuth();

  if (status === 'loading') {
    return <RouteFallback />;
  }

  if (status === 'unauthenticated') {
    return <Navigate to="/login" replace />;
  }

  return <Outlet />;
}
