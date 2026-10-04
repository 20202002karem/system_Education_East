import { useState, type FormEvent } from 'react';
import { Button } from '../../components/Button';
import { Input } from '../../components/Input';
import { useSnackbar } from '../../components/Snackbar';
import { ApiError } from '../../lib/apiClient';
import { organizationApi } from './organizationApi';

interface Props { open: boolean; siteId: number; onClose: () => void; onSaved: () => void; }

/** Batch 3 §3.5 — assigning a new manager auto-closes the previously active one. */
export function AssignManagerDialog({ open, siteId, onClose, onSaved }: Props) {
  const { notify } = useSnackbar();
  const [userId, setUserId] = useState('');
  const [from, setFrom] = useState(() => new Date().toISOString().slice(0, 10));
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  if (!open) return null;

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    setSubmitting(true);
    setError(null);
    try {
      await organizationApi.assignManager(siteId, { user_id: Number(userId), from });
      notify('تم تعيين مسؤول الموقع', 'success');
      onSaved();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'تعذّر تعيين المسؤول');
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div role="dialog" aria-modal="true" style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.4)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 1000 }}>
      <form onSubmit={handleSubmit} style={{ background: 'var(--color-surface)', borderRadius: 'var(--radius-md)', padding: 'var(--space-5)', width: '90%', maxWidth: 380, boxShadow: 'var(--shadow-md)' }}>
        <h2 style={{ marginTop: 0 }}>تعيين مسؤول موقع</h2>
        {error && <div role="alert" style={{ background: 'var(--color-danger-bg)', color: 'var(--color-danger)', padding: 'var(--space-3)', borderRadius: 'var(--radius-sm)', marginBottom: 'var(--space-4)' }}>{error}</div>}
        <Input label="معرّف المستخدم (User ID)" required inputMode="numeric" value={userId} onChange={(e) => setUserId(e.target.value)} />
        <Input label="تاريخ البدء" type="date" required value={from} onChange={(e) => setFrom(e.target.value)} />
        <p style={{ color: 'var(--color-text-muted)', fontSize: 'var(--font-size-sm)' }}>سيُنهى تلقائيًا تولي المسؤول الحالي الساري، إن وُجد، عند هذا التاريخ.</p>
        <div style={{ display: 'flex', gap: 'var(--space-3)', marginTop: 'var(--space-4)' }}>
          <Button type="submit" loading={submitting}>تعيين</Button>
          <Button type="button" variant="secondary" onClick={onClose} disabled={submitting}>إلغاء</Button>
        </div>
      </form>
    </div>
  );
}
