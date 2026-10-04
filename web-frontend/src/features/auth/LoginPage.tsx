import { useState, type FormEvent } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { useAuth } from '../../auth/AuthContext';
import { Button } from '../../components/Button';
import { Input } from '../../components/Input';
import { ApiError } from '../../lib/apiClient';

export function LoginPage() {
  const { login } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();

  const [identifier, setIdentifier] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    setError(null);
    setSubmitting(true);
    try {
      await login(identifier, password);
      const from = (location.state as { from?: Location })?.from?.pathname ?? '/users';
      navigate(from, { replace: true });
    } catch (e) {
      // Backend Arabic error messages are surfaced directly to the user.
      setError(e instanceof ApiError ? e.message : 'تعذّر تسجيل الدخول');
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div style={{ minHeight: '100%', display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 'var(--space-5)' }}>
      <form onSubmit={handleSubmit} style={{ width: '100%', maxWidth: 380, background: 'var(--color-surface)', padding: 'var(--space-6)', borderRadius: 'var(--radius-md)', boxShadow: 'var(--shadow-md)' }}>
        <h1 style={{ fontSize: 'var(--font-size-xl)', marginTop: 0 }}>تسجيل الدخول</h1>
        <p style={{ color: 'var(--color-text-muted)', marginTop: 0 }}>نظام قسم الحاسوب — مديرية شرق غزة</p>

        {error && (
          <div role="alert" style={{ background: 'var(--color-danger-bg)', color: 'var(--color-danger)', padding: 'var(--space-3)', borderRadius: 'var(--radius-sm)', marginBottom: 'var(--space-4)' }}>
            {error}
          </div>
        )}

        <Input
          label="البريد الإلكتروني"
          type="email"
          required
          autoComplete="username"
          value={identifier}
          onChange={(e) => setIdentifier(e.target.value)}
        />
        <Input
          label="كلمة المرور"
          type="password"
          required
          autoComplete="current-password"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
        />

        <Button type="submit" loading={submitting} style={{ width: '100%' }}>
          دخول
        </Button>
      </form>
    </div>
  );
}
