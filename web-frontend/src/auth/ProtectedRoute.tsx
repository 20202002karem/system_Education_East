import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { useAuth } from './AuthContext';
import type { Role } from '../lib/types';

/**
 * UX/security layer only — the authoritative check is always the backend
 * (instructions §7). Hiding a route here never substitutes for a 403 from
 * the API; it only avoids showing a page the user cannot act on anyway.
 */
export function ProtectedRoute({ roles }: { roles?: Role[] }) {
  const { status, user } = useAuth();
  const location = useLocation();

  if (status === 'initial' || status === 'loading') {
    return <div className="page-loading" role="status">جارٍ التحقق من الجلسة…</div>;
  }

  if (status !== 'authenticated' || !user) {
    return <Navigate to="/login" state={{ from: location }} replace />;
  }

  if (roles && !roles.includes(user.role)) {
    return <Navigate to="/403" replace />;
  }

  return <Outlet />;
}
