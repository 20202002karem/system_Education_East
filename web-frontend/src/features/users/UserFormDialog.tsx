import { useEffect, useState, type FormEvent } from 'react';
import { Button } from '../../components/Button';
import { Input } from '../../components/Input';
import { Select } from '../../components/Select';
import { useSnackbar } from '../../components/Snackbar';
import { ApiError } from '../../lib/apiClient';
import type { Role, User, ViewScope } from '../../lib/types';
import { usersApi } from './usersApi';

const roleOptions: { value: Role; label: string }[] = [
  { value: 'chairman', label: 'رئيس القسم' },
  { value: 'secretary', label: 'السكرتير' },
  { value: 'engineer', label: 'مهندس' },
  { value: 'technician', label: 'فني' },
  { value: 'school_manager', label: 'مدير مدرسة' },
];
const viewScopeOptions: { value: ViewScope; label: string }[] = [
  { value: 'own_site', label: 'موقعه فقط' },
  { value: 'sites', label: 'مواقع محددة' },
  { value: 'all', label: 'كل القسم' },
  { value: 'assigned_only', label: 'ما أُسند إليه فقط' },
];

interface Props {
  open: boolean;
  mode: 'create' | 'edit';
  user?: User;
  onClose: () => void;
  onSaved: () => void;
}

/** Batch 3 §3.2 — site_scope_ids required iff view_scope=sites (422 otherwise, surfaced via fieldErrors). */
export function UserFormDialog({ open, mode, user, onClose, onSaved }: Props) {
  const { notify } = useSnackbar();
  const [name, setName] = useState(user?.name ?? '');
  const [email, setEmail] = useState(user?.email ?? '');
  const [role, setRole] = useState<Role>(user?.role ?? 'technician');
  const [viewScope, setViewScope] = useState<ViewScope>(user?.view_scope ?? 'assigned_only');
  const [specialization, setSpecialization] = useState(user?.specialization ?? '');
  const [siteScopeIds, setSiteScopeIds] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});
  const [formError, setFormError] = useState<string | null>(null);

  useEffect(() => {
    if (open) {
      setName(user?.name ?? '');
      setEmail(user?.email ?? '');
      setRole(user?.role ?? 'technician');
      setViewScope(user?.view_scope ?? 'assigned_only');
      setSpecialization(user?.specialization ?? '');
      setSiteScopeIds('');
      setFieldErrors({});
      setFormError(null);
    }
  }, [open, user]);

  if (!open) return null;

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    setSubmitting(true);
    setFieldErrors({});
    setFormError(null);
    const parsedSiteIds = siteScopeIds
      .split(',')
      .map((s) => s.trim())
      .filter(Boolean)
      .map(Number);

    try {
      if (mode === 'create') {
        await usersApi.create({
          name,
          login_identifier: email,
          role,
          view_scope: viewScope,
          specialization: specialization || undefined,
          site_scope_ids: viewScope === 'sites' ? parsedSiteIds : undefined,
        });
        notify('تم إنشاء الحساب بنجاح', 'success');
      } else if (user) {
        await usersApi.update(user.id, {
          name,
          role,
          view_scope: viewScope,
          specialization: specialization || null,
          site_scope_ids: viewScope === 'sites' ? parsedSiteIds : undefined,
        });
        notify('تم حفظ التعديلات', 'success');
      }
      onSaved();
    } catch (e) {
      if (e instanceof ApiError && e.code === 'validation_failed' && e.fields) {
        setFieldErrors(e.fields);
      } else if (e instanceof ApiError && e.code === 'duplicate_identifier') {
        setFieldErrors({ login_identifier: [e.message] });
      } else {
        setFormError(e instanceof ApiError ? e.message : 'تعذّر الحفظ');
      }
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div role="dialog" aria-modal="true" style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.4)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 1000 }}>
      <form onSubmit={handleSubmit} style={{ background: 'var(--color-surface)', borderRadius: 'var(--radius-md)', padding: 'var(--space-5)', width: '90%', maxWidth: 480, boxShadow: 'var(--shadow-md)' }}>
        <h2 style={{ marginTop: 0 }}>{mode === 'create' ? 'إنشاء حساب' : 'تعديل حساب'}</h2>

        {formError && <div role="alert" style={{ background: 'var(--color-danger-bg)', color: 'var(--color-danger)', padding: 'var(--space-3)', borderRadius: 'var(--radius-sm)', marginBottom: 'var(--space-4)' }}>{formError}</div>}

        <Input label="الاسم" required value={name} onChange={(e) => setName(e.target.value)} error={fieldErrors.name?.[0]} />
        <Input label="البريد الإلكتروني" type="email" required disabled={mode === 'edit'} value={email} onChange={(e) => setEmail(e.target.value)} error={fieldErrors.login_identifier?.[0]} />
        <Select label="الدور" value={role} onChange={(e) => setRole(e.target.value as Role)} options={roleOptions} />
        <Select label="نطاق العرض" value={viewScope} onChange={(e) => setViewScope(e.target.value as ViewScope)} options={viewScopeOptions} />
        {viewScope === 'sites' && (
          <Input
            label="مواقع النطاق (أرقام المعرّفات مفصولة بفاصلة)"
            value={siteScopeIds}
            onChange={(e) => setSiteScopeIds(e.target.value)}
            error={fieldErrors.site_scope_ids?.[0]}
            hint="مثال: 12,47"
          />
        )}
        <Input label="التخصص (اختياري)" value={specialization ?? ''} onChange={(e) => setSpecialization(e.target.value)} />

        <div style={{ display: 'flex', gap: 'var(--space-3)', marginTop: 'var(--space-5)' }}>
          <Button type="submit" loading={submitting}>حفظ</Button>
          <Button type="button" variant="secondary" onClick={onClose} disabled={submitting}>إلغاء</Button>
        </div>
      </form>
    </div>
  );
}
