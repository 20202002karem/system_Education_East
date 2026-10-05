import { useEffect, useState, type FormEvent } from 'react';
import { Button } from '../../components/Button';
import { Input } from '../../components/Input';
import { Select } from '../../components/Select';
import { useSnackbar } from '../../components/Snackbar';
import { ApiError } from '../../lib/apiClient';
import type { Asset, ManualAssetStatus } from '../../lib/types';
import { assetsApi } from './assetsApi';
import { manualStatusOptions } from './assetLabels';

interface Props { open: boolean; asset: Asset; onClose: () => void; onSaved: () => void; }

/** API-AST-05: only the four manual statuses are offered; reason optional (OI-API-02 open). */
export function StatusChangeDialog({ open, asset, onClose, onSaved }: Props) {
  const { notify } = useSnackbar();
  const [to, setTo] = useState<ManualAssetStatus>('working');
  const [reason, setReason] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    if (open) {
      setTo(manualStatusOptions.find((o) => o.value !== asset.status)?.value ?? 'working');
      setReason(''); setError(null);
    }
  }, [open, asset.status]);

  if (!open) return null;

  async function submit(e: FormEvent) {
    e.preventDefault();
    setSubmitting(true); setError(null);
    try {
      await assetsApi.changeStatus(asset.id, { to_status: to, reason: reason.trim() || null, version: asset.version });
      notify('تم تحديث الحالة', 'success');
      onSaved();
    } catch (err) {
      const msg = err instanceof ApiError ? (err.fields?.to_status?.[0] ?? err.message) : 'تعذّر تحديث الحالة';
      setError(msg);
      if (err instanceof ApiError && err.code === 'version_conflict') onSaved(); // reload the card
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div role="dialog" aria-modal="true" style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.4)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 1000 }}>
      <form onSubmit={submit} style={{ background: 'var(--color-surface)', borderRadius: 'var(--radius-md)', padding: 'var(--space-5)', width: '90%', maxWidth: 420, boxShadow: 'var(--shadow-md)' }}>
        <h2 style={{ marginTop: 0 }}>تحديث حالة الجهاز</h2>
        <Select label="الحالة الجديدة" value={to} onChange={(e) => setTo(e.target.value as ManualAssetStatus)} options={manualStatusOptions} error={error ?? undefined} />
        <Input label="السبب (اختياري)" value={reason} onChange={(e) => setReason(e.target.value)} maxLength={255} />
        <div style={{ display: 'flex', gap: 'var(--space-3)', marginTop: 'var(--space-5)' }}>
          <Button type="submit" loading={submitting} disabled={to === asset.status}>تحديث</Button>
          <Button type="button" variant="secondary" onClick={onClose} disabled={submitting}>إلغاء</Button>
        </div>
      </form>
    </div>
  );
}
