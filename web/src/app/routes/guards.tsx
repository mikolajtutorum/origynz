import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { useAuthStore } from '@core/auth/store';

// Gate for authenticated areas. Replaces Laravel's `auth` middleware.
export function RequireAuth() {
  const status = useAuthStore((s) => s.status);
  const location = useLocation();

  if (status !== 'authenticated') {
    return <Navigate to="/login" replace state={{ from: location }} />;
  }
  return <Outlet />;
}

// Keep authenticated users out of the login/register screens.
export function RedirectIfAuthenticated() {
  const status = useAuthStore((s) => s.status);
  if (status === 'authenticated') {
    return <Navigate to="/dashboard" replace />;
  }
  return <Outlet />;
}

// Site administration — replaces Laravel's `super.admin` middleware.
export function RequireSuperAdmin() {
  const user = useAuthStore((s) => s.user);
  if (!user?.is_super_admin) {
    return <Navigate to="/dashboard" replace />;
  }
  return <Outlet />;
}
