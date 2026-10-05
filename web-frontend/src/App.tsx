import { Navigate, Route, Routes } from 'react-router-dom';
import { AuthProvider, useAuth } from './auth/AuthContext';
import { ProtectedRoute } from './auth/ProtectedRoute';
import { SnackbarProvider } from './components/Snackbar';
import { AppShell } from './layout/AppShell';
import { LoginPage } from './features/auth/LoginPage';
import { UsersListPage } from './features/users/UsersListPage';
import { UserDetailPage } from './features/users/UserDetailPage';
import { SitesListPage } from './features/organization/SitesListPage';
import { SiteDetailPage } from './features/organization/SiteDetailPage';
import { SettingsPage } from './features/settings/SettingsPage';
import { ReferenceDataPage } from './features/settings/ReferenceDataPage';
import { AssetsListPage } from './features/assets/AssetsListPage';
import { AssetDetailPage } from './features/assets/AssetDetailPage';
import { AuditLogPage } from './features/audit/AuditLogPage';
import { ChainChecksPage } from './features/audit/ChainChecksPage';

function RootRedirect() {
  const { status, user } = useAuth();
  if (status === 'initial' || status === 'loading') return <div className="page-loading">جارٍ التحميل…</div>;
  return <Navigate to={status === 'authenticated' ? (user?.role === 'chairman' ? '/users' : '/assets') : '/login'} replace />;
}

function ForbiddenPage() {
  return (
    <div style={{ padding: 'var(--space-6)', textAlign: 'center' }}>
      <h1>غير مسموح</h1>
      <p>لا تملك صلاحية الوصول إلى هذه الصفحة.</p>
    </div>
  );
}

function NotFoundPage() {
  return (
    <div style={{ padding: 'var(--space-6)', textAlign: 'center' }}>
      <h1>الصفحة غير موجودة</h1>
    </div>
  );
}

export default function App() {
  return (
    <AuthProvider>
      <SnackbarProvider>
        <Routes>
          <Route path="/" element={<RootRedirect />} />
          <Route path="/login" element={<LoginPage />} />
          <Route path="/403" element={<ForbiddenPage />} />

          {/* M2 Assets — every role may read (scope enforced server-side). */}
          <Route element={<ProtectedRoute />}>
            <Route element={<AppShell />}>
              <Route path="/assets" element={<AssetsListPage />} />
              <Route path="/assets/:id" element={<AssetDetailPage />} />
            </Route>
          </Route>

          {/* All M1 admin screens are chairman-only per Batch 3 §4. */}
          <Route element={<ProtectedRoute roles={['chairman']} />}>
            <Route element={<AppShell />}>
              <Route path="/users" element={<UsersListPage />} />
              <Route path="/users/:id" element={<UserDetailPage />} />
              <Route path="/sites" element={<SitesListPage />} />
              <Route path="/sites/:id" element={<SiteDetailPage />} />
              <Route path="/settings" element={<SettingsPage />} />
              <Route path="/reference" element={<ReferenceDataPage />} />
              <Route path="/audit" element={<AuditLogPage />} />
              <Route path="/audit/chain-checks" element={<ChainChecksPage />} />
            </Route>
          </Route>

          <Route path="*" element={<NotFoundPage />} />
        </Routes>
      </SnackbarProvider>
    </AuthProvider>
  );
}
