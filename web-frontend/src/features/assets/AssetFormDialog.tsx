import { useEffect, useState, type FormEvent } from 'react';
import { Button } from '../../components/Button';
import { Input } from '../../components/Input';
import { Select } from '../../components/Select';
import { useSnackbar } from '../../components/Snackbar';
import { ApiError } from '../../lib/apiClient';
import type { Asset } from '../../lib/types';
import { assetsApi } from './assetsApi';
import type { useAssetLookups } from './useAssetLookups';

interface Props {
  open: boolean;
  mode: 'create' | 'edit';
  asset?: Asset;
  lookups: ReturnType<typeof useAssetLookups>;
  onClose: () => void;
  onSaved: () => void;
}

/** Create (API-AST-01) / edit descriptive data (API-AST-04: category + holder only). */
export function AssetFormDialog({ open, mode, asset, lookups, onClose, onSaved }: Props) {
  const { notify } = useSnackbar();
  const [inventoryNo, setInventoryNo] = useState('');
  const [serialNo, setSerialNo] = useState('');
  const [categoryId, setCategoryId] = useState('');
  const [siteId, setSiteId] = useState('');
  const [holder, setHolder] = useState('');
  const [legacy, setLegacy] = useState('');
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    if (open) {
      setInventoryNo(''); setSerialNo(''); setLegacy(''); setSiteId('');
      setCategoryId(asset ? String(asset.category_id) : '');
      setHolder(asset?.holder_text ?? '');
      setFieldErrors({});
    }
  }, [open, asset]);

  if (!open) return null;

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    setSubmitting(true);
    setFieldErrors({});
    try {
      if (mode === 'create') {
        const legacyNumbers = legacy.split('\n').map((s) => s.trim()).filter(Boolean);
        await assetsApi.create({
          inventory_no: inventoryNo.trim(),
          serial_no: serialNo.trim() || null,
          category_id: Number(categoryId),
          current_site_id: Number(siteId),
          holder_text: holder.trim() || null,
          legacy_numbers: legacyNumbers.length ? legacyNumbers : undefined,
        });
        notify('تمت إضافة الجهاز', 'success');
      } else if (asset) {
        await assetsApi.update(asset.id, {
          version: asset.version,
          category_id: Number(categoryId),
          holder_text: holder.trim() || null,
        });
        notify('تم حفظ التعديلات', 'success');
      }
      onSaved();
    } catch (err) {
      if (err instanceof ApiError && err.fields) {
        setFieldErrors(err.fields);
        if (err.status === 409 || err.status === 422) notify(err.message, 'error');
      } else {
        notify(err instanceof ApiError ? err.message : 'تعذّر الحفظ', 'error');
      }
    } finally {
      setSubmitting(false);
    }
  }

  const idInput = (label: string, value: string, set: (v: string) => void, key: string) => (
    <Input label={label} required type="number" min={1} value={value} onChange={(e) => set(e.target.value)} error={fieldErrors[key]?.[0]}
      hint={lookups.available ? undefined : 'أدخل المعرّف (القائمة غير متاحة لدورك)'} />
  );

  return (
    <div role="dialog" aria-modal="true" style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.4)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 1000, overflowY: 'auto' }}>
      <form onSubmit={handleSubmit} style={{ background: 'var(--color-surface)', borderRadius: 'var(--radius-md)', padding: 'var(--space-5)', width: '90%', maxWidth: 480, boxShadow: 'var(--shadow-md)', margin: 'var(--space-4) 0' }}>
        <h2 style={{ marginTop: 0 }}>{mode === 'create' ? 'إضافة جهاز' : 'تعديل بيانات الجهاز'}</h2>
        {mode === 'create' && (
          <>
            <Input label="رقم الجرد" required value={inventoryNo} onChange={(e) => setInventoryNo(e.target.value)} error={fieldErrors.inventory_no?.[0]} />
            <Input label="الرقم التسلسلي (اختياري)" value={serialNo} onChange={(e) => setSerialNo(e.target.value)} error={fieldErrors.serial_no?.[0]} />
          </>
        )}
        {lookups.available ? (
          <Select label="التصنيف" required value={categoryId} onChange={(e) => setCategoryId(e.target.value)} error={fieldErrors.category_id?.[0]}
            options={[{ value: '', label: 'اختر…' }, ...lookups.categories.map((c) => ({ value: String(c.id), label: c.name }))]} />
        ) : idInput('معرّف التصنيف', categoryId, setCategoryId, 'category_id')}
        {mode === 'create' && (lookups.available ? (
          <Select label="الموقع" required value={siteId} onChange={(e) => setSiteId(e.target.value)} error={fieldErrors.current_site_id?.[0]}
            options={[{ value: '', label: 'اختر…' }, ...lookups.sites.map((s) => ({ value: String(s.id), label: s.name_ar }))]} />
        ) : idInput('معرّف الموقع', siteId, setSiteId, 'current_site_id'))}
        <Input label="الحائز" value={holder} onChange={(e) => setHolder(e.target.value)} error={fieldErrors.holder_text?.[0]} />
        {mode === 'create' && (
          <div style={{ marginBottom: 'var(--space-4)' }}>
            <label htmlFor="legacy" style={{ display: 'block', fontSize: 'var(--font-size-sm)', color: 'var(--color-text-muted)', marginBottom: 'var(--space-1)' }}>أرقام الجرد القديمة (رقم في كل سطر)</label>
            <textarea id="legacy" rows={3} value={legacy} onChange={(e) => setLegacy(e.target.value)} style={{ width: '100%' }} />
            {fieldErrors.legacy_numbers?.[0] && <p style={{ color: 'var(--color-danger)', fontSize: 'var(--font-size-sm)' }}>{fieldErrors.legacy_numbers[0]}</p>}
          </div>
        )}
        <div style={{ display: 'flex', gap: 'var(--space-3)', marginTop: 'var(--space-5)' }}>
          <Button type="submit" loading={submitting}>حفظ</Button>
          <Button type="button" variant="secondary" onClick={onClose} disabled={submitting}>إلغاء</Button>
        </div>
      </form>
    </div>
  );
}
