import { NavLink, Outlet } from 'react-router-dom';
import { useAuth } from '../auth/AuthContext';
import { Button } from '../components/Button';

/**
 * Navigation is built from the user's role (instructions §4). In M1 every
 * admin area (Users, Organization, Settings, Audit) is chairman-only per the
 * Authorization Matrix (Batch 3 §4) — so non-chairman roles see only the
 * "current user" area. This is UX convenience only; the backend still
 * enforces every request server-side regardless of what's shown here.
 */
const navItems = [
  { to: '/users', label: 'المستخدمون', roles: ['chairman'] as const },
  { to: '/sites', label: 'المواقع', roles: ['chairman'] as const },
  { to: '/settings', label: 'الإعدادات', roles: ['chairman'] as const },
  { to: '/reference', label: 'القوائم المرجعية', roles: ['chairman'] as const },
  { to: '/audit', label: 'سجل التدقيق', roles: ['chairman'] as const },
];

export function AppShell() {
  const { user, logout, hasRole } = useAuth();

  return (
    <div style={{ minHeight: '100%', display: 'flex', flexDirection: 'column' }}>
      <header style={{ background: 'var(--color-surface)', borderBottom: '1px solid var(--color-border)', padding: 'var(--space-3) var(--space-5)', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
        <div>
          <strong>نظام قسم الحاسوب — مديرية شرق غزة</strong>
        </div>
        <div style={{ display: 'flex', alignItems: 'center', gap: 'var(--space-4)' }}>
          <span style={{ fontSize: 'var(--font-size-sm)', color: 'var(--color-text-muted)' }}>
            {user?.name} · {roleLabel(user?.role)}
          </span>
          <Button variant="secondary" onClick={() => void logout()}>تسجيل الخروج</Button>
        </div>
      </header>

      <div style={{ display: 'flex', flex: 1 }}>
        <nav style={{ width: 220, borderInlineEnd: '1px solid var(--color-border)', padding: 'var(--space-4)', background: 'var(--color-surface)' }} className="hide-on-mobile">
          {navItems
            .filter((item) => hasRole(...item.roles))
            .map((item) => (
              <NavLink
                key={item.to}
                to={item.to}
                style={({ isActive }) => ({
                  display: 'block',
                  padding: 'var(--space-2) var(--space-3)',
                  borderRadius: 'var(--radius-sm)',
                  marginBottom: 'var(--space-1)',
                  color: isActive ? 'var(--color-primary)' : 'var(--color-text)',
                  background: isActive ? 'var(--color-bg)' : 'transparent',
                  textDecoration: 'none',
                })}
              >
                {item.label}
              </NavLink>
            ))}
        </nav>

        <main style={{ flex: 1, padding: 'var(--space-5)' }}>
          <Outlet />
        </main>
      </div>
    </div>
  );
}

function roleLabel(role?: string): string {
  const map: Record<string, string> = {
    chairman: 'رئيس القسم',
    secretary: 'السكرتير',
    engineer: 'مهندس',
    technician: 'فني',
    school_manager: 'مدير مدرسة',
  };
  return role ? map[role] ?? role : '';
}
