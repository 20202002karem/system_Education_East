import { useEffect, useState, type FormEvent } from 'react';
import { Button } from '../../components/Button';
import { Input } from '../../components/Input';
import { Select } from '../../components/Select';
import { ConfirmDialog } from '../../components/ConfirmDialog';
import { useSnackbar } from '../../components/Snackbar';
import { ApiError } from '../../lib/apiClient';
import type { Asset } from '../../lib/types';
import { assetsApi } from './assetsApi';

interface Props { open: boolean; asset: Asset; onClose: () => void; onSaved: () => void; }

/** API-AST-07 — chairman only (DD-2). Sensitive: reason required + explicit confirmation step. */
export function IdentifierCorrectionDialog({ open, asset, onClose, onSaved }: Props) {
  const { notify } = useSnackbar();
  const [field, setField] = useState<'inventory_no' | 'serial_no'>('inventory_no');
  const [newValue, setNewValue] = useState('');
  const [reason, setReason] = useState('');
  const [confirming, setConfirming] = useState(false);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    if (open) { setField('inventory_no'); setNewValue(''); setReason(''); setConfirming(false); setFieldErrors({}); }
  }, [open]);

  if (!open) return null;

  async function run() {
    setSubmitting(true); setFieldErrors({});
    try {
      await assetsApi.correctIdentifier(asset.id, { field_name: field, new_value: newValue.trim(), reason: reason.trim() });
      notify('تم تصحيح الرقم وتسجيله في السجل', 'success');
      onSaved();
    } catch (err) {
      setConfirming(false);
      if (err instanceof ApiError && err.fields) setFieldErrors(err.fields);
      notify(err instanceof ApiError ? err.message : 'تعذّر التصحيح', 'error');
    } finally {
      setSubmitting(false);
    }
  }

  const current = field === 'inventory_no' ? asset.inventory_no : asset.serial_no ?? '—';

  return (
    <>
      <div role="dialog" aria-modal="true" style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.4)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 1000 }}>
        <form onSubmit={(e: FormEvent) => { e.preventDefault(); setConfirming(true); }}
          style={{ background: 'var(--color-surface)', borderRadius: 'var(--radius-md)', padding: 'var(--space-5)', width: '90%', maxWidth: 440, boxShadow: 'var(--shadow-md)' }}>
          <h2 style={{ marginTop: 0 }}>تصحيح رقم حساس</h2>
          <Select label="الحقل" value={field} onChange={(e) => setField(e.target.value as 'inventory_no' | 'serial_no')}
            options={[{ value: 'inventory_no', label: 'رقم الجرد' }, { value: 'serial_no', label: 'الرقم التسلسلي' }]} />
          <p style={{ color: 'var(--color-text-muted)' }}>القيمة الحالية: <strong>{current}</strong></p>
          <Input label="القيمة الجديدة" required value={newValue} onChange={(e) => setNewValue(e.target.value)} error={fieldErrors.new_value?.[0] ?? fieldErrors[field]?.[0]} />
          <Input label="سبب التصحيح" required value={reason} onChange={(e) => setReason(e.target.value)} maxLength={255} error={fieldErrors.reason?.[0]} />
          <div style={{ display: 'flex', gap: 'var(--space-3)', marginTop: 'var(--space-5)' }}>
            <Button type="submit" disabled={!newValue.trim() || !reason.trim()}>متابعة</Button>
            <Button type="button" variant="secondary" onClick={onClose}>إلغاء</Button>
          </div>
        </form>
      </div>
      <ConfirmDialog open={confirming} title="تأكيد تصحيح الرقم" variant="danger" loading={submitting}
        description={`سيتم تغيير ${field === 'inventory_no' ? 'رقم الجرد' : 'الرقم التسلسلي'} من «${current}» إلى «${newValue.trim()}». تبقى القيمة القديمة في السجل.`}
        confirmLabel="تأكيد التصحيح" onConfirm={() => void run()} onCancel={() => setConfirming(false)} />
    </>
  );
}
