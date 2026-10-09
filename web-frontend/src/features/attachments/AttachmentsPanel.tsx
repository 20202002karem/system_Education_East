import { useCallback, useEffect, useRef, useState } from 'react';
import { Card } from '../../components/Card';
import { Button } from '../../components/Button';
import { useSnackbar } from '../../components/Snackbar';
import { LoadingState, EmptyState, ErrorState } from '../../components/states';
import { ApiError } from '../../lib/apiClient';
import type { Attachment } from '../../lib/types';
import { attachmentsApi } from './attachmentsApi';

/** M3-API-030/031/037. Upload + list + temporary signed download. No delete, no replace (IN-01/IN-20). */
export function AttachmentsPanel({ ownerType, ownerId, canUpload }: { ownerType: Attachment['owner_type']; ownerId: number; canUpload: boolean }) {
  const { notify } = useSnackbar();
  const input = useRef<HTMLInputElement>(null);
  const [rows, setRows] = useState<Attachment[] | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  const load = useCallback(async () => {
    setError(null);
    try { setRows((await attachmentsApi.list(ownerType, ownerId)).data); }
    catch (e) { setError(e instanceof ApiError ? e.message : 'تعذّر تحميل المرفقات'); }
  }, [ownerType, ownerId]);
  useEffect(() => { void load(); }, [load]);

  async function upload(file: File | undefined) {
    if (!file) return;
    setBusy(true);
    try { await attachmentsApi.upload(ownerType, ownerId, file); notify('تم رفع المرفق', 'success'); await load(); }
    catch (e) { notify(e instanceof ApiError ? e.message : 'تعذّر رفع المرفق', 'error'); }
    finally { setBusy(false); if (input.current) input.current.value = ''; }
  }

  async function download(a: Attachment) {
    try { const { url } = await attachmentsApi.downloadLink(a.id); window.open(url, '_blank', 'noopener'); }
    catch (e) { notify(e instanceof ApiError ? e.message : 'تعذّر إنشاء رابط التنزيل', 'error'); }
  }

  return (
    <Card title="المرفقات" actions={canUpload ? (
      <>
        <input ref={input} type="file" aria-label="اختيار ملف" hidden onChange={(e) => void upload(e.target.files?.[0])} />
        <Button variant="secondary" loading={busy} onClick={() => input.current?.click()}>رفع مرفق</Button>
      </>
    ) : undefined}>
      {error && <ErrorState message={error} onRetry={load} />}
      {!error && rows === null && <LoadingState />}
      {!error && rows !== null && rows.length === 0 && <EmptyState title="لا توجد مرفقات" />}
      {!error && rows !== null && rows.length > 0 && (
        <ul style={{ listStyle: 'none', padding: 0, margin: 0 }}>
          {rows.map((a) => (
            <li key={a.id} style={{ display: 'flex', justifyContent: 'space-between', gap: 'var(--space-3)', padding: 'var(--space-2) 0', borderBottom: '1px solid var(--color-border)' }}>
              <span>{a.original_filename} <small style={{ color: 'var(--color-text-muted)' }}>({Math.ceil(a.size_bytes / 1024)} ك.ب)</small></span>
              <Button variant="ghost" onClick={() => void download(a)}>تنزيل</Button>
            </li>
          ))}
        </ul>
      )}
    </Card>
  );
}
