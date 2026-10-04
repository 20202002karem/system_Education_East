import { useEffect, useState, type FormEvent } from 'react';
import { Button } from '../../components/Button';
import { Input } from '../../components/Input';
import { Select } from '../../components/Select';
import { useSnackbar } from '../../components/Snackbar';
import { ApiError } from '../../lib/apiClient';
import type { Site, SiteType } from '../../lib/types';
import { organizationApi } from './organizationApi';

const typeOptions: { value: SiteType; label: string }[] = [
  { value: 'school', label: 'مدرسة' },
  { value: 'department', label: 'قسم' },
  { value: 'warehouse', label: 'Warehouse' },
];

interface Props { open: boolean; mode: 'create' | 'edit'; site?: Site; onClose: () => void; onSaved: () => void; }

export function SiteFormDialog({ open, mode, site, onClose, onSaved }: Props) {
  const { notify } = useSnackbar();
  const [type, setType] = useState<SiteType>(site?.type ?? 'school');
  const [code, setCode] = useState(site?.code ?? '');
  const [nameAr, setNameAr] = useState(site?.name_ar ?? '');
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    if (open) {
      setType(site?.type ?? 'school');
      setCode(site?.code ?? '');
      setNameAr(site?.name_ar ?? '');
      setFieldErrors({});
    }
  }, [open, site]);

  if (!open) return null;

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    setSubmitting(true);
    setFieldErrors({});
    try {
      if (mode === 'create') {
        await organizationApi.createSite({ type, code, name_ar: nameAr });
        notify('تم إنشاء الموقع', 'success');
      } else if (site) {
        await organizationApi.updateSite(site.id, { type, code, name_ar: nameAr });
        notify('تم حفظ التعديلات', 'success');
      }
      onSaved();
    } catch (e) {
      if (e instanceof ApiError && (e.code === 'validation_failed' || e.status === 409) && e.fields) {
        setFieldErrors(e.fields);
      } else if (e instanceof ApiError && e.status === 409) {
        setFieldErrors({ code: ['رمز الموقع مستخدم مسبقًا'] });
      } else {
        notify(e instanceof ApiError ? e.message : 'تعذّر الحفظ', 'error');
      }
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div role="dialog" aria-modal="true" style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.4)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 1000 }}>
      <form onSubmit={handleSubmit} style={{ background: 'var(--color-surface)', borderRadius: 'var(--radius-md)', padding: 'var(--space-5)', width: '90%', maxWidth: 420, boxShadow: 'var(--shadow-md)' }}>
        <h2 style={{ marginTop: 0 }}>{mode === 'create' ? 'إنشاء موقع' : 'تعديل موقع'}</h2>
        <Select label="النوع" value={type} onChange={(e) => setType(e.target.value as SiteType)} options={typeOptions} />
        <Input label="الرمز" required value={code} onChange={(e) => setCode(e.target.value)} error={fieldErrors.code?.[0]} />
        <Input label="الاسم" required value={nameAr} onChange={(e) => setNameAr(e.target.value)} error={fieldErrors.name_ar?.[0]} />
        <div style={{ display: 'flex', gap: 'var(--space-3)', marginTop: 'var(--space-5)' }}>
          <Button type="submit" loading={submitting}>حفظ</Button>
          <Button type="button" variant="secondary" onClick={onClose} disabled={submitting}>إلغاء</Button>
        </div>
      </form>
    </div>
  );
}
